<?php


require_once('functions.php');
require_once('db.php');

use Magento\Framework\App\Bootstrap;
require __DIR__ . '/../app/bootstrap.php';

$dbhost = 'db-niponb2b';
$dbuser = 'magento';
$dbpass = 'MTg5ZDQ1ZgStr5'; //magento;
$dbname = 'magento';

$db = new db($dbhost, $dbuser, $dbpass, $dbname);

logger('Init Update'," ---- ".date("Y-m-d H:i:s"));

$categories = getAllCategories();
//var_dump($categories);
$products = $products_stock = array();
$cant = 0;

//$categories = array();
//$categories["Response"][] = array("CodigoSubCategoria"=>10204);

foreach($categories["Response"] as $category){
    echo "Category: ".$category["CodigoSubCategoria"]."\n";
    $sap_products = getAllProductsByCategory($category["CodigoSubCategoria"]);

    //productos
    foreach($sap_products["MaestroSAP"] as $sap_prod){
        $products[$sap_prod["CodigoArticulo"]] = $sap_prod;
        $products[$sap_prod["CodigoArticulo"]]["SubCategory"] = $category["CodigoSubCategoria"];
    }
    
    $stock = 0;
    foreach($sap_products["StockDisponiblePorBodega"] as $sap_stock){
        //stock
        if (!array_key_exists($sap_stock["SKU"], $products_stock)) {
            $products_stock[$sap_stock["SKU"]] = 0;
        }
        if($sap_stock["CodigoBodega"] == "00CD" OR $sap_stock["CodigoBodega"] == "ALAMEDA" OR $sap_stock["CodigoBodega"] == "PORTUGAL" OR $sap_stock["CodigoBodega"] == "VICUNA"){
            $stock = $sap_stock["Disponible"];
            if ((int)$sap_stock["Disponible"] < 0){
                logger('Stock < 0', $sap_stock["SKU"].' stock:'.$sap_stock["Disponible"]);
                $stock = 0;
            }
            $products_stock[$sap_stock["SKU"]] += $stock;
        }
    }
    /* 
    $cant++;
    if($cant == 3)
        break; 
    */
}

$list_attr = getAttributeIds($db);
$list_magento_stock_prod = getAllMagentoProductStock($db);
$list_magento_price_prod = getAllMagentoProductPrice($db,$list_attr);
$list_magento_price_prod_by_sku = $list_magento_price_prod[0];
$list_magento_price_prod_by_entity = $list_magento_price_prod[1];
$list_magento_prod = getAllSimpleMagentoProducts($db);

logger('Cant Product SAP',"Cant: ".count($products));
logger('Cant Product Magento',"Cant: ".count($list_magento_prod));

$count_price = $count_stock = 0;

//Price
$enable_price = $disable_price = $stock_minus = $stock_magento_gt_sap = $stock_magento_lt_sap = $price_magento_gt_sap = $price_magento_lt_sap = $price_no_change = $stock_no_change = 0;
logger('Init Price'," ---- ".date("Y-m-d H:i:s"));

foreach($products as $key => $product){
    if(key_exists($key, $list_magento_prod)){//product exists in magento
        if(key_exists($key, $list_magento_price_prod_by_sku)){ //product exists in magento price list
            $price_magento = (int)$list_magento_price_prod_by_sku[$key]["price"];
            if($product["PrecioMayor"] < 100){
                $entity_id = $list_magento_price_prod_by_sku[$key]["entity_id"];
                $status = 0;
                updateStatus($db, $entity_id, $status, $list_attr);
                logger('UpdateStatus',"DISABLE 1- sku: ".$key);
                $disable_price ++;
            }else{
                if($price_magento != $product["PrecioMayor"]){
                    $entity_id = $list_magento_price_prod_by_sku[$key]["entity_id"];
                    $status = 1;
                    if ($product["Visible"] == 'N'){
                        $status = 0;
                        logger('UpdateStatus',"DISABLE 1- sku: ".$key);
                    }else{
                        logger('UpdateStatus',"ENABLE - sku: ".$key);
                    }
                    updateStatus($db, $entity_id, $status, $list_attr);
                    $enable_price ++;
                    $price = $product["PrecioMayor"];
                    //update price
                    $result = updatePrice($db, $entity_id, $price, $list_attr);
                    if ($price_magento > $price){
                        $price_magento_gt_sap++;
                    }else{
                        $price_magento_lt_sap++;
                    }
                    logger('updatePrice',"sku: ".$key." | Magento price: ".$price_magento." | SAP price: ".$price . ' | result_price: '.$result);
                    $count_price++;
                    
                }else{
                    logger('updatePrice',"sku: ".$key." | Magento price: ".$price_magento." | SAP price: ". $product["PrecioMayor"] . ' | No Change');
                    $price_no_change++;
                }   
            }
        }else{
            $price = $product["PrecioMayor"];
            if($price < 100){
                $entity_id = $list_magento_prod[$key]["entity_id"];
                $status = 0;
                updateStatus($db, $entity_id, $status, $list_attr);
                logger('UpdateStatus',"DISABLE 2 - sku: ".$key);
                $disable_price ++;
            }
            $entity_id = $list_magento_prod[$key]["entity_id"];
            echo "2. insertPrice ". $key." - ".$entity_id;
            $result = insertPrice($db, $entity_id, $price, $list_attr);

            $price_magento_lt_sap++;
            
            logger('insertPrice',"sku: ".$key." / ".$entity_id." | Magento price: ???  | SAP price: ".$price . ' | result_price: '.$result);
            $count_price++;
        }
    }else{
        logger('updatePrice',"PRODUCTO NO EXISTE - sku: ".$key,"products-no-exists_");
    }

}


//Stock
logger('Init Stock'," ---- ".date("Y-m-d H:i:s"));
foreach($products as $key => $product){
    if(key_exists($key, $list_magento_stock_prod)){
        $stock_magento = (int)$list_magento_stock_prod[$key]["qty"];
       // if($stock_magento != $products_stock[$key]){
            $entity_id = $list_magento_stock_prod[$key]["entity_id"];
            $stock = $products_stock[$key];
            if($stock < 0){
                $stock = 0;
                $stock_minus ++;
                logger('updateStock',"sku: ".$key." | Stock < 0 | stock: ". $products_stock[$key]);
            }
            $is_in_stock = ($stock > 1) ? 1:0;
            //update stock
            
            $result = updateStock($db, $entity_id, $stock, $is_in_stock);
            logger('updateStock',"sku: ".$key." | Magento qty: ".$stock_magento." | SAP stock: ".$stock . ' | result_stock: '.$result);
            if ($stock_magento > $stock){
                $stock_magento_gt_sap++;
            }else{
                $stock_magento_lt_sap++;
            }
            $count_stock++;
        //}else{
        //    $stock_no_change++;
        //}	
    }
}

logger('Enable by price > 100',$enable_price);
logger('Disable by price < 100',$disable_price);
logger('Stock < 0 ',$stock_minus);
logger('Stock Magento greater than SAP',$stock_magento_gt_sap);
logger('Stock Magento less than SAP',$stock_magento_lt_sap);
logger('Price Magento greater than SAP',$price_magento_gt_sap);
logger('Price Magento less than SAP',$price_magento_lt_sap);
logger('Price equal to SAP',$price_magento_lt_sap);
logger('Stock equal to SAP',$price_magento_lt_sap);
logger('END Update'," ---- ".date("Y-m-d H:i:s"));


?>
