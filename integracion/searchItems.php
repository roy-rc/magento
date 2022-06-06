<?php


require_once('functions.php');
require_once('db.php');

use Magento\Framework\App\Bootstrap;
require '../app/bootstrap.php';

$dbhost = 'db-niponb2b';
$dbuser = 'magento';
$dbpass = 'MTg5ZDQ1ZgStr5';//'magento';
$dbname = 'magento';

$db = new db($dbhost, $dbuser, $dbpass, $dbname);

$log_file = "search_items";
logger('Init'," ---- ".date("Y-m-d H:i:s"),$log_file);

$categories = getAllCategories();
var_dump($categories);
$products = $products_stock = array();
$cant = 0;

/* $categories = array();
$categories["Response"][] = array("CodigoSubCategoria"=>10204);
$categories["Response"][] = array("CodigoSubCategoria"=>10801);
$categories["Response"][] = array("CodigoSubCategoria"=>10806); */
$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();
$appState = $objectManager->get('\Magento\Framework\App\State');

$categoryFactory = $objectManager->create('Magento\Catalog\Model\ResourceModel\Category\CollectionFactory');
$categories_mg = $categoryFactory->create()                              
    ->addAttributeToSelect('*')
    ->addFieldToFilter('level', ['eq' => 3]);
;
$list_category = array();
foreach ($categories_mg as $category){
    if($category->getName() != 'Autos a escala'){
        $list_category[$category->getUrlPath()] =  $category->getName();
        $key_category_mg[strtolower(replaceCharacter($category->getName()))] =  $category->getUrlPath();
        echo $category->getUrlPath()." - ".strtolower(replaceCharacter($category->getName()))."\n";
    }
    
}

$eavConfig = $objectManager->create('\Magento\Eav\Model\Config');
$attribute = $eavConfig->getAttribute('catalog_product', 'nipon_marca');
$options = $attribute->getSource()->getAllOptions();
foreach($options as $item){
    $list_marca_mg[$item["label"]] = $item["value"];
}
$attribute = $eavConfig->getAttribute('catalog_product', 'nipon_modelo');
$options = $attribute->getSource()->getAllOptions();
foreach($options as $item){
    $list_modelo_mg[$item["label"]] = $item["value"];
}
$attribute = $eavConfig->getAttribute('catalog_product', 'nipon_years');
$options = $attribute->getSource()->getAllOptions();
foreach($options as $item){
    $list_years_mg[$item["label"]] = $item["value"];
}

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

        $stock = $sap_stock["Disponible"];
        if ((int)$sap_stock["Disponible"] < 0){
            logger('Stock < 0', $sap_stock["SKU"].' stock:'.$sap_stock["Disponible"]);
            $stock = 0;
        }
        $products_stock[$sap_stock["SKU"]] += $stock;
    }

    foreach($sap_products["Response"] as $sap_attr){
        //attr oem is key
        $attr[$sap_attr["CodigoMaestro"]][] = $sap_attr; 
        $cant++;
    }
}

$list_brand = array();
$list_model = array();
$list_year = array();
$list_only_brand = array();
$list_only_model = array();
$list_only_year = array();
foreach($products as $sku => $item){
    $show = true;
    if($item["Price"] < 100){
        $show = false;
    }
    if ($item["Visible"] == 'N'){
        $show = false;
    }
    $stock = $products_stock[$sku];
    if($stock < 0){
        $show = false;
    }
    if($show){
        $category_name = strtolower($item["NombreSubCategoria"]);
        $key_category = $key_category_mg[$category_name];
        foreach($attr[$item["OEM"]] as $simple){
            if(!key_exists($key_category, $list_brand)){
                $list_brand[$key_category] = array();
            }
            if(!in_array($simple["Marca"], $list_brand[$key_category])){
                if($simple["Marca"] != '.' and $simple["Marca"] != 'null')
                    $list_brand[$key_category][] = $simple["Marca"];
            }
            if(!key_exists($key_category.'-'.$simple["Marca"], $list_model)){
                $list_model[$key_category.'-'.$simple["Marca"]] = array();
            }
            if(!in_array($simple["Modelo"], $list_model[$key_category.'-'.$simple["Marca"]])){
                $list_model[$key_category.'-'.$simple["Marca"]][] = $simple["Modelo"];
            }
            foreach (range($simple["AnoInicio"], $simple["AnoTermino"]) as $year) {
                if(!key_exists($key_category."-".$simple["Marca"]."-".$simple["Modelo"], $list_year)){
                    $list_year[$key_category."-".$simple["Marca"]."-".$simple["Modelo"]]=array();
                }
                if(!in_array($year, $list_year[$key_category."-".$simple["Marca"]."-".$simple["Modelo"]])){
                    $list_year[$key_category."-".$simple["Marca"]."-".$simple["Modelo"]][] = $year;
                }
            };
            //Only marcas modelos y años 
            if(!in_array($simple["Marca"], $list_only_brand)){
                if($simple["Marca"] != '.' and $simple["Marca"] != 'null')
                    $list_only_brand[$list_marca_mg[$simple["Marca"]]] = $simple["Marca"];
            }
            if(!in_array($simple["Modelo"], $list_only_model[$simple["Marca"]])){
                $list_only_model[$simple["Marca"]][$list_modelo_mg[$simple["Modelo"]]] = $simple["Modelo"];
            }
            foreach (range($simple["AnoInicio"], $simple["AnoTermino"]) as $year) {
                if(!in_array($year, $list_only_year[$simple["Marca"]."-".$simple["Modelo"]])){
                    $list_only_year[$simple["Modelo"]][$list_years_mg[$year]] = $year;
                }
            };
        }
    }
}

foreach($list_brand as $key=>$item){
    asort($list_brand[$key] );
}
foreach($list_model as $key=>$item){
    asort($list_model[$key] );
}
foreach($list_year as $key=>$item){
    asort($list_year[$key] );
}

foreach($list_only_model as $key=>$item){
    asort($list_only_model[$key] );
}
foreach($list_only_year as $key=>$item){
    asort($list_only_year[$key] );
}

$fp = fopen(__DIR__ . '/../pub/searchitems/category.json', 'w');
asort($list_category);
fwrite($fp, json_encode($list_category));
fclose($fp);

$fp = fopen(__DIR__ . '/../pub/searchitems/brand.json', 'w');
fwrite($fp, json_encode($list_brand));
fclose($fp);

$fp = fopen(__DIR__ . '/../pub/searchitems/model.json', 'w');
fwrite($fp, json_encode($list_model));
fclose($fp);

$fp = fopen(__DIR__ . '/../pub/searchitems/year.json', 'w');
fwrite($fp, json_encode($list_year));
fclose($fp);

$fp = fopen(__DIR__ . '/../pub/searchitems/brand_only.json', 'w');
asort($list_only_brand);
fwrite($fp, json_encode($list_only_brand));
fclose($fp);

$fp = fopen(__DIR__ . '/../pub/searchitems/model_only.json', 'w');
fwrite($fp, json_encode($list_only_model));
fclose($fp);

$fp = fopen(__DIR__ . '/../pub/searchitems/year_only.json', 'w');
fwrite($fp, json_encode($list_only_year));
fclose($fp);
?>
