<?php
require_once('functions.php');
require_once('db.php');

use Magento\Framework\App\Bootstrap;
require __DIR__ . '/../app/bootstrap.php';

//$bootstrap = Bootstrap::create(BP, $_SERVER);

$dbhost = 'db-niponb2b';
$dbuser = 'magento';
$dbpass = 'MTg5ZDQ1ZgStr5';//'magento';//
$dbname = 'magento';

$db = new db($dbhost, $dbuser, $dbpass, $dbname);
$file_name = 'creacion_';
logger('Init'," ---- ", $file_name);

$categories = getAllCategories();
//var_dump($categories);
$products = $stock = $attr = $price = $images = array();
$cant = 0;
$count_mgs_brand = 0;
//$categories = array();
//$categories["Response"][] = array("CodigoSubCategoria"=>10204);

//Get All products from SAP
foreach($categories["Response"] as $category){
    //echo "Category: ".$category["CodigoSubCategoria"]."\n";
    $sap_products = getAllProductsByCategory($category["CodigoSubCategoria"]);

    foreach($sap_products["MaestroSAP"] as $sap_prod){
        //productos
        $products[$sap_prod["CodigoArticulo"]] = $sap_prod;
        $products[$sap_prod["CodigoArticulo"]]["SubCategory"] = $category["CodigoSubCategoria"];
    }

    /* foreach($sap_products["StockDisponiblePorBodega"] as $sap_stock){
        //stock
        if($sap_stock["CodigoBodega"] == "00CD"){
            $stock[$sap_stock["SKU"]] = $sap_stock["Disponible"];
        }
    } */
    $stock_qty = 0;
    foreach($sap_products["StockDisponiblePorBodega"] as $sap_stock){
        //stock
        if (!array_key_exists($sap_stock["SKU"], $stock)) {
            $stock[$sap_stock["SKU"]] = 0;
        }
        if($sap_stock["CodigoBodega"] == "00CD" OR $sap_stock["CodigoBodega"] == "ALAMEDA" OR $sap_stock["CodigoBodega"] == "PORTUGAL" OR $sap_stock["CodigoBodega"] == "VICUNA"){
            $stock_qty = $sap_stock["Disponible"];
            if ((int)$sap_stock["Disponible"] < 0){
                logger('Stock < 0', $sap_stock["SKU"].' stock:'.$sap_stock["Disponible"], $file_name);
                $stock_qty = 0;
            }
            $stock[$sap_stock["SKU"]] += $stock_qty;
        }
    }

    foreach($sap_products["Response"] as $sap_attr){
        //attr oem is key
        $attr[$sap_attr["CodigoMaestro"]][] = $sap_attr; 
        $cant++;
    }

    foreach($sap_products["Imagenes"] as $sap_img){
        //imagenes
        $images[$sap_img["SKU"]] = $sap_img;
    }
}

    $bootstrap = Bootstrap::create(BP, $_SERVER);
    $objectManager = $bootstrap->getObjectManager();
    $appState = $objectManager->get('\Magento\Framework\App\State');
    $appState->setAreaCode('adminhtml');

    $list_attr_ids = getAttributeIds($db);

    //get all configurable products from magento
    $list_conf_prod = getAllMagentoProducts($db);
    $list_simple_prod = getAllSimpleMagentoProducts($db);
    $list_categories = getAllMagentoCategories($db);

    $cant_create_simp = $cant_update_simp = 0; 

    foreach( $products as $sku => $item ){
        //if($sku == 'N-26719-C997T-3'):
        //echo "SKU:".$sku."\n";
        if(!key_exists($sku, $list_simple_prod)){
            echo "Create:".$sku."\n";
            //create product in magento
            logger('Create Sku',$sku." // ".$item['PrecioMayor']." // ".$stock[$sku], $file_name);
            $category = array();
            if(key_exists(strtoupper(replaceCharacter($item["NombreSubCategoria"])), $list_categories)){
                $category_key = strtoupper(replaceCharacter($item["NombreSubCategoria"]));
                $category = array($list_categories[$category_key]);
            }else{
                logger('Category does not exist:',$sku ." // ".$item["NombreSubCategoria"], $file_name);
            }
            $years = array();
            $marca = array();
            $modelo = array();
            $motor = array();
            $cilindrada = array();
            $all_years = array();
            if(key_exists($item["OEM"], $attr)){
                $sap_simple = $attr[$item["OEM"]];
                foreach($sap_simple as $simple){
                    if(!in_array(strtoupper($simple["Marca"]), $marca)){
                        $marca[] = strtoupper($simple["Marca"]);
                    }
                    if(!in_array(strtoupper($simple["Modelo"]), $modelo)){
                        $modelo[] = strtoupper($simple["Modelo"]);
                    }
                    if(!in_array(strtoupper($simple["Motor"]), $motor)){
                        $motor[] = strtoupper($simple["Motor"]);
                    }
                    if(!in_array(strtoupper($simple["Cilindrada"]), $cilindrada)){
                        $cilindrada[] = strtoupper($simple["Cilindrada"]);
                    }
                    if($simple["AnoInicio"] < 1980){
                        $simple["AnoInicio"] = 1980;
                    }
                    if($simple["AnoTermino"] < 1980 OR $simple["AnoTermino"] > 2022){
                        $simple["AnoTermino"] = $simple["AnoInicio"];
                    }
                    foreach (range($simple["AnoInicio"], $simple["AnoTermino"]) as $year) {
                        if(!in_array($year, $years)){
                            $years[] = $year;
                        }
                    };
                    $all_years['Years'] = $years;
                }
            }
            

            $mg_product_simple = createProduct($item, $stock[$sku], $objectManager, $category, "simple"); //create simple product
            $entity_id = $mg_product_simple->getId();
            updateProductAttMultiSelect($db, $entity_id, $list_attr_ids["nipon_marca"], $marca, "insert");
            updateProductAttMultiSelect($db, $entity_id, $list_attr_ids["nipon_modelo"], $modelo, "insert");
            updateProductAttMultiSelect($db, $entity_id, $list_attr_ids["nipon_motor"], $motor, "insert");
            updateProductAttMultiSelect($db, $entity_id, $list_attr_ids["nipon_cilindrada"], $cilindrada, "insert");
            updateProductAttMultiSelect($db, $entity_id, $list_attr_ids["nipon_years"], $all_years['Years'], "insert");
            $cant_create_simp++;
        }else{
            echo "Update:".$sku."\n";
            //actualzar simples
            logger('Update Sku',$sku." // ".$item['PrecioMayor']." // ".$stock[$sku], $file_name);
            $list_attr_value = getAllMagentoProductAttr($db, $sku);
            
            checkProductData($db, $list_attr_ids, $item, $list_attr_value[$sku]);
            $years = array();
            $marca = array();
            $modelo = array();
            $motor = array();
            $cilindrada = array();
            $all_years = array();
            if(key_exists($item["OEM"], $attr)){
                $sap_simple = $attr[$item["OEM"]];
                foreach($sap_simple as $simple){
                    if(!in_array(strtoupper($simple["Marca"]), $marca)){
                        $marca[] = strtoupper($simple["Marca"]);
                    }
                    if(!in_array(strtoupper($simple["Modelo"]), $modelo)){
                        $modelo[] = strtoupper($simple["Modelo"]);
                    }
                    if(!in_array(strtoupper($simple["Motor"]), $motor)){
                        $motor[] = strtoupper($simple["Motor"]);
                    }
                    if(!in_array(strtoupper($simple["Cilindrada"]), $cilindrada)){
                        $cilindrada[] = strtoupper($simple["Cilindrada"]);
                    }
                    if($simple["AnoInicio"] < 1980){
                        $simple["AnoInicio"] = 1980;
                    }
                    if($simple["AnoTermino"] < 1980 OR $simple["AnoTermino"] > 2022){
                        $simple["AnoTermino"] = $simple["AnoInicio"];
                    }
                    foreach (range($simple["AnoInicio"], $simple["AnoTermino"]) as $year) {
                        if(!in_array($year, $years)){
                            $years[] = $year;
                        }
                    };
                    $all_years['Years'] = $years;
                }
            }
            
            $entity_id = $list_attr_value[$sku]["entity_id"];
            updateProductAttMultiSelect($db, $entity_id, $list_attr_ids["nipon_marca"], $marca, "update");
            updateProductAttMultiSelect($db, $entity_id, $list_attr_ids["nipon_modelo"], $modelo, "update");
            updateProductAttMultiSelect($db, $entity_id, $list_attr_ids["nipon_motor"], $motor, "update");
            updateProductAttMultiSelect($db, $entity_id, $list_attr_ids["nipon_cilindrada"], $cilindrada, "update");
            updateProductAttMultiSelect($db, $entity_id, $list_attr_ids["nipon_years"], $all_years['Years'], "update");
            $cant_update_simp++;
        }

        //MGS Brand
        $list_magento_mgs_brand = getAllMagentoMgsBrand($db);
        var_dump($list_magento_mgs_brand);
        echo key_exists(strtoupper($item["MarcaOrigen"]),$list_magento_mgs_brand)."\n";
        if(key_exists(strtoupper($item["MarcaOrigen"]),$list_magento_mgs_brand) and key_exists($sku, $list_simple_prod)){
            $brand_id = $list_magento_mgs_brand[strtoupper($item["MarcaOrigen"])]["brand_id"];
            $entity_id = $list_simple_prod[$sku]["entity_id"];
            $list_magento_product_mgs_brand = getAllMagentoProductMgsBrand($db);
            if(key_exists($sku, $list_magento_product_mgs_brand)){
                $item_brand = $list_magento_product_mgs_brand[$sku];
                if(!key_exists($brand_id, $item_brand["brand_ids"])){
                    $result = updateProductMgsBrand($db, $brand_id, $entity_id);
                    $count_mgs_brand++;
                    logger('update MgsBrandProduct',$result, $file_name);
                }
            }else{
                $result = insertProductMgsBrand($db, $brand_id, $entity_id);
                $count_mgs_brand++;
                logger('insert MgsBrandProduct',$result, $file_name);
            }
        }else{
            logger('Mgs Brand', 'Marca no existe en MGS:'.strtoupper($item["MarcaOrigen"]).' O SKU no existe: '.$sku, $file_name);
            if(!key_exists(strtoupper($item["MarcaOrigen"]),$list_magento_mgs_brand)){
                $brand_id = insertMgsBrand($db,$item["MarcaOrigen"],1);
                logger('insert MgsBrand', "MgsBrandId: " .$brand_id . " BrandName: " .$item["MarcaOrigen"], $file_name);
            }
            if(key_exists($sku, $list_simple_prod)){
                $result = insertProductMgsBrand($db, $brand_id, $entity_id);
                $count_mgs_brand++;
                logger('insert MgsBrandProduct',$result, $file_name);
            }
        }
        //endif;
    }
    //////////////////////////////////////////////////////////////////////////////////////////////////

    logger('Create product simple cant',$cant_create_simp, $file_name);
    logger('Update product simple cant',$cant_update_simp, $file_name);
    logger('MgsBrand cant',$count_mgs_brand, $file_name);

    $fp = fopen('data_sap.csv', 'w');
    $line = array("n","sku","oem","name","price","stock");
    fputcsv($fp, $line);
    $cant = 1;
    foreach($products as $product){
        fputcsv($fp, array($cant++, $product["CodigoArticulo"], $product["OEM"], $product["NombreArticulo"], $product["PrecioMayor"], $stock[$product["CodigoArticulo"]] ));
    }

    fclose($fp);
//createFile("sap_".date("Y-m-d_H-i-s"),$list_prod);
?>
