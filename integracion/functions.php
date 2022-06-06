<?php

use Magento\Framework\App\Bootstrap;
require __DIR__ . '/../app/bootstrap.php';

function logger($code,$msg, $file_name = 'integracion_'){
    $log = "[execution - ".date("Y-m-d H:i:s")."]: Code:".$code." // ".$msg.PHP_EOL;
    file_put_contents(__DIR__ . '/log/'.$file_name.date("Y-m-d").'.log', $log, FILE_APPEND);
}

function createFile($file_name,$data){
    $fp = fopen('./log/'.$file_name.'.csv', 'w'); 
    foreach ($data as $fields) { 
        fputcsv($fp, $fields); 
    } 
    fclose($fp); 
    logger('createFile',$file_name);
}

//Get all products by category from SAP
function getAllProductsByCategory($category_id){
    $url = "http://201.238.200.3:8000/WS/services/item/getArticuloB2CT.xsjs?categoria=".$category_id."&fecha=".date('Ymd');
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_URL,$url);
    if (($result = curl_exec($ch)) === FALSE) {
        logger('connectSAP',"cURL error".curl_error($ch),"error_");
        die();
    } else {
        logger('connectSAP',"Done"); 
    }
    curl_close($ch);
    $result = str_replace("\n", '', str_replace("\r", '', $result) );

    $result = json_decode($result, true); 
    if($result["ResponseStatus"] === "Error"){
        logger('connectSAP',"Error:".$result["Response"]["message"]["value"]); 
        return array("MaestroSAP"=>array(), "StockDisponiblePorBodega"=>array());
    }else{
        return $result;
    }
    
    //VERIFICAR ERROR
    /*
    {
    "ResponseStatus": "Error",
    "ResponseType": "Message",
    "ResponseCount": 1,
    "Response": {
        "code": -900100122,
        "message": {
            "lang": "en-us",
            "value": "ERROR Connect() ConnectorSL.xsjslib: HttpClient.getResponse: Can't get the response from the server. The following error occured: internal error occurred "Connection timed out while reading response from 192.168.68.235""
            }
        }
    }*/
   
}

//Get all products by category from SAP
function getAllProductsByCategory_V2($category_id){
    $url = "http://201.238.200.3:8000/WS/services/item/getArticuloB2C_V2.xsjs?categoria=".$category_id."&fecha=".date('Ymd');
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_URL,$url);
    if (($result = curl_exec($ch)) === FALSE) {
        logger('connectSAP',"cURL error".curl_error($ch),"error_");
        die();
    } else {
        logger('connectSAP',"Done"); 
    }
    curl_close($ch);
    $result = str_replace("\n", '', str_replace("\r", '', $result) );

    $result = json_decode($result, true); 
    if($result["ResponseStatus"] === "Error"){
        logger('connectSAP',"Error:".$result["Response"]["message"]["value"]); 
        return array("MaestroSAP"=>array(), "StockDisponiblePorBodega"=>array());
    }else{
        return $result;
    }
    
    //VERIFICAR ERROR
    /*
    {
    "ResponseStatus": "Error",
    "ResponseType": "Message",
    "ResponseCount": 1,
    "Response": {
        "code": -900100122,
        "message": {
            "lang": "en-us",
            "value": "ERROR Connect() ConnectorSL.xsjslib: HttpClient.getResponse: Can't get the response from the server. The following error occured: internal error occurred "Connection timed out while reading response from 192.168.68.235""
            }
        }
    }*/
   
}

//Get all categories from SAP
function getAllCategories(){
    $url = "http://201.238.200.3:8000/WS/services/item/getSubCategoria.xsjs";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_URL,$url);
    $result=curl_exec($ch);
    curl_close($ch);
    return json_decode($result, true);
}

function createProduct2($product, $stock){
    $bootstrap = Bootstrap::create(BP, $_SERVER);
    $objectManager = $bootstrap->getObjectManager();
    $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); // instance of object manager

    $appState = $objectManager->get('\Magento\Framework\App\State');
    $appState->setAreaCode('adminhtml');

    $product = $objectManager->create('\Magento\Catalog\Model\Product');
    $product->setSku('my-sku'); // Set your sku here
    $product->setName('Sample Simple Product'); // Name of Product
    $product->setAttributeSetId(4); // Attribute set id
    $product->setStatus(1); // Status on product enabled/ disabled 1/0
    $product->setWeight(10); // weight of product
    $product->setVisibility(4); // visibilty of product (catalog / search / catalog, search / Not visible individually)
    $product->setTaxClassId(2); // Tax class id
    $product->setTypeId('simple'); // type of product (simple/virtual/downloadable/configurable)
    $product->setPrice(100); // price of product
    $product->setStockData(
                            array(
                                'use_config_manage_stock' => 0,
                                'manage_stock' => 1,
                                'is_in_stock' => 1,
                                'qty' => 999999999
                            )
                        );
    $product->save();
    exit();
}
//Create product in magento
function createProduct($product, $stock, $objectManager, $category = array(),$type){
    $name = $product["NombreArticulo"] . ' ' . $product["MarcaOrigen"] . ' ' .$product["Procedencia"] ;
        
    $url = preg_replace('#[^0-9a-z]+#i', '-', $product['NombreArticulo'].'-'.$product['CodigoArticulo']);
    $url = strtolower($url);
    
    $product_obj = $objectManager->create('\Magento\Catalog\Model\Product');
    $product_obj->setSku($product['CodigoArticulo']); 
    $product_obj->setName($name);
    $product_obj->setShortDescription($product['NombreAlternativo']); 
    $product_obj->setAttributeSetId(4); 
    $product_obj->setUrlKey($url);
    $status = 0;
    if($product['Visible'] == 'Y' AND $product['Price'] > 100 ){ 
        $status = 1;
    }
    $product_obj->setStatus($status); 
    $product_obj->setCategoryIds($category); 
    $product_obj->setVisibility(4); //Catalog, Search
    
    $product_obj->setTaxClassId(2); 
    $product_obj->setTypeId($type);
    $product_obj->setWebsiteIds(array(1));	

    $weight = $product["Peso"] > 0 ? $product["Peso"]:1;
    $product_obj->setWeight($weight); 

    //$product_obj->setOtrosCodigos($product["Procedencia"]);
    //$product_obj->setDimensiones($product["Procedencia"]);
    //$product_obj->setTablaAplicaciones($product["Procedencia"]);   
    $product_obj->setSkuMestro($product["CodigoArticulo"]);
    $product_obj->setOem($product["OEM"]);

    $product_obj->setProcedenciaPropducto($product["Procedencia"]);
    $product_obj->setMarcaProducto($product["MarcaOrigen"]);   
    $product_obj->setAncho((int)$product["Ancho"]);   
    $product_obj->setVolumen((int)$product["Volumen"]);
    $product_obj->setAltura((int)$product["Alto"]);
    $product_obj->setLongitud((int)$product["Largo"]);

    if($product['PriceSpecialPeriod'] && $product['PriceSpecialPeriod'] > 0 && $product['PeriodDesde'] != '' && $product['PeriodHasta'] != ''){
        $product_obj->setSpecialPrice($product['PriceSpecialPeriod']);
        $product_obj->setSpecialFromDate($product['PeriodDesde']);
        $product_obj->setSpecialFromDateIsFormated(true);
        $product_obj->setSpecialToDate($product['PeriodHasta']);
        $product_obj->setSpecialToDateIsFormated(true); 
    }

    $product_obj->setPrice($product['Price']);
    $product_obj->setStockData(
        array( 
            'use_config_manage_stock' => 0,
            'manage_stock' => 1,
            'min_sale_qty' => 1,
            'is_in_stock' => $stock > 0 ? 1:0,
            'qty' => $stock > 0 ? $stock:0,
        )
    );   

    $product_obj->save();
    logger('create Product',"sku: ".$product['CodigoArticulo']);
    return $product_obj;
}

//uPDATE product in magento
function updateProduct($product_obj, $product, $stock, $objectManager, $category = array(),$type){

    if($type == "configurable"){
        $name = $product["NombreArticulo"] . ' ' . $product["MarcaOrigen"] . ' ' .$product["Procedencia"] ;
    }else{
        $name = $product["NombreArticulo"] . ' ' . $product["Marca"] . ' ' .$product["Modelo"] ;
    }
    
    
    $url = preg_replace('#[^0-9a-z]+#i', '-', $product['NombreArticulo'].'-'.$product['CodigoArticulo']);
    $url = strtolower($url);
    
    $product_obj->setName($name);
    $product_obj->setShortDescription($product['NombreAlternativo']); 
    $product_obj->setAttributeSetId(4); 
    $product_obj->setUrlKey($url);
    $status = 0;
    if($product['Visible'] == 'Y' AND $product['Price'] > 100 ){ 
        $status = 1;
    }
    $product_obj->setStatus($status); 
    if($type == "configurable"){
        $product_obj->setVisibility(4); //Catalog, Search
        $product_obj->setCategoryIds($category); 
    }else{
        $product_obj->setVisibility(1);//Not Visible Individually
    }
    
    $product_obj->setTaxClassId(2); 
    $product_obj->setTypeId($type);
    $product_obj->setWebsiteIds(array(1));	

    $weight = $product["Peso"] > 0 ? $product["Peso"]:1;
    $product_obj->setWeight($weight); 

    //$product_obj->setOtrosCodigos($product["Procedencia"]);
    //$product_obj->setDimensiones($product["Procedencia"]);
    //$product_obj->setTablaAplicaciones($product["Procedencia"]);   
    $product_obj->setSkuMestro($product["CodigoArticulo"]);
    $product_obj->setOem($product["OEM"]);

    $product_obj->setProcedenciaPropducto($product["Procedencia"]);
    $product_obj->setMarcaProducto($product["MarcaOrigen"]);   
    $product_obj->setAncho((int)$product["Ancho"]);   
    $product_obj->setVolumen((int)$product["Volumen"]);
    $product_obj->setAltura((int)$product["Alto"]);
    $product_obj->setLongitud((int)$product["Largo"]);

    /* if($product['PriceSpecialPeriod'] && $product['PriceSpecialPeriod'] > 0 && $product['PeriodDesde'] != '' && $product['PeriodHasta'] != ''){
        $product_obj->setSpecialPrice($product['PriceSpecialPeriod']);
        $product_obj->setSpecialFromDate($product['PeriodDesde']);
        $product_obj->setSpecialFromDateIsFormated(true);
        $product_obj->setSpecialToDate($product['PeriodHasta']);
        $product_obj->setSpecialToDateIsFormated(true); 
    } */

    /* $product_obj->setPrice($product['Price']);
    $product_obj->setStockData(
        array( 
            'use_config_manage_stock' => 0,
            'manage_stock' => 1,
            'min_sale_qty' => 1,
            'is_in_stock' => $stock > 0 ? 1:0,
            'qty' => $stock > 0 ? $stock:0,
        )
    ); */   

    $product_obj->save();
    logger('create Product',"sku: ".$product['CodigoArticulo']);
    return $product_obj;
}

function listAttrKeySAP($key){
    //Magento attr => SAP attr
    $list_att_key_sap = array(
        "nipon_marca"=>"Marca",
        "nipon_modelo"=>"Modelo",
        "nipon_motor"=>"Motor",
        "nipon_cilindrada"=>"Cilindrada",
        //"sku_mestro"=>"CodigoArticulo",
        "longitud"=>"Largo",
        "ancho"=>"Ancho",
        "altura"=>"Alto",
        "volumen"=>"Volumen",
        "procedencia_producto"=>"Procedencia",
        "marca_producto"=>"MarcaOrigen",
        "oem"=>"OEM",
        "weight"=>"Peso",
        "description"=>"NombreAlternativo",
        "short_description"=>"NombreAlternativo",
        "name"=>"NombreArticulo",
        "mgs_brand"=>"MarcaOrigen",
    );
    return $list_att_key_sap[$key];
}

function getTypeAttr(){
    $list_att_key_magento = array(
        //'nipon_marca' => "select",
        //'nipon_modelo' => "select",
        //'nipon_motor' => "select",
        //'nipon_cilindrada' => "select",
        //'nipon_years' => "varchar",
        'name' => "varchar",
        //'sku_mestro' => "varchar",
        'longitud' => "varchar",
        'ancho' => "varchar",
        'altura' => "varchar",
        'volumen' => "varchar",
        'procedencia_producto' => "varchar",
        'marca_producto' => "varchar",
        'oem' => "varchar",
        //'precio_mayorista' => "decimal",
        'weight' => "decimal",
        'description' => "text",
        'short_description' => "text",
        'mgs_brand' => "select",
        //'meta_keywords' => "text",
        //'meta_title' => "varchar",
        //'meta_description' => "varchar",
    );
    return $list_att_key_magento;
}

function getAttributeIds($db){
    $sql = "select attribute_id, attribute_code 
        from eav_attribute 
        where 
        entity_type_id = 4 and 
        (attribute_code='price' or 
        attribute_code='special_price' or 
        attribute_code = 'special_from_date' or 
        attribute_code = 'special_to_date' or
        attribute_code = 'longitud' or
        attribute_code = 'ancho' or
        attribute_code = 'altura' or
        attribute_code = 'volumen' or
        attribute_code = 'sku_mestro' or
        attribute_code = 'nipon_years' or
        attribute_code = 'nipon_motor' or
        attribute_code = 'nipon_modelo' or
        attribute_code = 'nipon_marca' or
        attribute_code = 'nipon_cilindrada' or
        attribute_code = 'procedencia_producto' or
        attribute_code = 'marca_producto' or
        attribute_code = 'oem' or
        attribute_code = 'dimensiones' or
        attribute_code = 'otros_codigos' or
        attribute_code = 'tabla_aplicaciones' or
        attribute_code = 'precio_mayorista' or
        attribute_code = 'weight' or
        attribute_code = 'description' or
        attribute_code = 'short_description' or
        attribute_code = 'meta_title' or
        attribute_code = 'meta_keywords' or
        attribute_code = 'meta_description' or
        attribute_code = 'country_of_manufacture' or
        attribute_code = 'name' or
        attribute_code = 'mgs_brand' or
        attribute_code = 'status'
        ) order by attribute_id;";
    $items = $db->query($sql)->fetchAll();
    $list = array();
    foreach($items as $item){
        $list[$item['attribute_code']] = $item['attribute_id'];
    }  
    return $list;
}

function getAllMagentoProductAttr($db, $sku){
    $sql = "select prod.attribute_set_id, prod.sku, prod.entity_id,
    attr_name.attribute_id, attr_name.attribute_code,
    attr_op.option_id, op_val.value
    from catalog_product_entity prod
    inner join catalog_product_entity_int attr on prod.entity_id = attr.entity_id
    inner join eav_attribute attr_name on attr.attribute_id = attr_name.attribute_id
    inner join eav_attribute_option attr_op on attr.attribute_id = attr_op.attribute_id
    inner join eav_attribute_option_value op_val on attr_op.option_id = op_val.option_id and attr.value = op_val.option_id
    WHERE prod.sku = '{$sku}' and attr.store_id = 0 and op_val.store_id = 0 
    union 
    select prod.attribute_set_id, prod.sku, prod.entity_id,
    attr_name.attribute_id, attr_name.attribute_code,'',attr_input.value
    from catalog_product_entity prod
    inner join catalog_product_entity_varchar attr_input on prod.entity_id = attr_input.entity_id 
    inner join eav_attribute attr_name on attr_input.attribute_id = attr_name.attribute_id
    where prod.sku = '{$sku}' and attr_input.store_id = 0 ;";
    $items = $db->query($sql)->fetchAll();
    $list = array();
    if(count($items)){
        $list[$items[0]['sku']] = array(
            "sku" => $items[0]['sku'],
            "entity_id" =>$items[0]['entity_id'],
            "attribute_set_id" =>$items[0]['attribute_set_id'],
        );
    }
    foreach($items as $item){
        $list[$item['sku']][$item['attribute_code']] = array(
            "attribute_id" => $item['attribute_id'],
            "attribute_code" => $item['attribute_code'],
            "option_id" => $item['option_id'],
            "value"=>$item['value'],
        );
    }
    return $list;
}

function checkProductData($db, $list_attr, $product, $list_attr_value,$type="simple"){
    $return = '';
    $list_att_key_magento = getTypeAttr();
    foreach($list_att_key_magento as $attr_code => $tipo){
        $action = '-';
        $process = false;
        if(key_exists($attr_code, $list_attr_value)){
            echo " ---- ".$attr_code."\n";
           
            if(trim(strtolower($list_attr_value[ $attr_code ]['value'])) != trim(strtolower($product[listAttrKeySAP($attr_code)]))){
                $process = true;
                $action = 'update';
            }
            
        }else{
            $process = true;   
            $action = 'insert';   
        }

        if($process){
            echo " -- ".$attr_code."\n";
            $do_stuff = false;
            if($type=="configurable" and !in_array($attr_code,array("nipon_marca","nipon_modelo","nipon_motor","nipon_cilindrada","nipon_years"))){
                $do_stuff = true;
            }
            if($type=="simple" and $attr_code != "nipon_years"){
                $do_stuff = true;
            }
            if($do_stuff){
                $new_value = trim(str_replace('"', '', str_replace("'", "", $product[listAttrKeySAP($attr_code)])));
                $new_attr_id = $list_attr[$attr_code];
                if($tipo == 'select'){
                    if($attr_code == "mgs_brand"){
                        
                    }
                    $return .= updateProductAttrSelect($db, $list_attr_value["entity_id"], $new_attr_id ,$new_value,$action);
                }else{
                    $return .= updateProductAttVarchar($db, $list_attr_value["entity_id"], $new_attr_id ,$new_value,$action);
                }
            }
        }
    }
    return $return;
}
//Get all configurable products
function getAllMagentoProducts($db){
    $sql = "select sku, product.entity_id, type_id from catalog_product_entity product where type_id = 'configurable';";
    $items = $db->query($sql)->fetchAll();
    $list = array();
    foreach($items as $item){
        $list[$item['sku']] = array(
            "sku" => $item['sku'],
            "entity_id" => $item['entity_id'],
        );
    }
    return $list;
}

//Get all simple products
function getAllSimpleMagentoProducts($db){
    $sql = "select sku, product.entity_id, type_id from catalog_product_entity product where type_id = 'simple';";
    $items = $db->query($sql)->fetchAll();
    $list = array();
    foreach($items as $item){
        $list[$item['sku']] = array(
            "sku" => $item['sku'],
            "entity_id" => $item['entity_id'],
        );
    }
    return $list;
}

//Get all child products
function getAllChildrenProduct($db, $entity_id){
    $sql = "select child_id from catalog_product_relation where parent_id = {$entity_id};";
    $items = $db->query($sql)->fetchAll();
    $list = array();
    foreach($items as $item){
        $list[] = $item['child_id'];
    }
    return $list;
}

//Get all products an stock from magento
function getAllMagentoProductStock($db){
    $sql = "select sku, product_id, qty, is_in_stock
        from cataloginventory_stock_item stock
        inner join catalog_product_entity product on product.entity_id = stock.product_id";
    $items = $db->query($sql)->fetchAll();
    $list = array();
    foreach($items as $item){
        $list[$item['sku']] = array(
            "sku" => $item['sku'],
            "entity_id" => $item['product_id'],
            "qty" => $item['qty'],
            "is_in_stock" => $item['is_in_stock'],
        );
    }
    return $list;
}

//Get all products and price from magento
function getAllMagentoProductPrice($db, $list_attr){
    /* $sql = "select sku, product.entity_id, p1.value as price, p2.value as special_price
        from catalog_product_entity product
        inner join catalog_product_entity_decimal p1 on product.entity_id = p1.entity_id and p1.attribute_id = {$list_attr['price']}
        inner join catalog_product_entity_decimal p2 on product.entity_id = p2.entity_id and p2.attribute_id = {$list_attr['special_price']} ";
     */    
    $sql = "select sku, product.entity_id, p1.value as price
        from catalog_product_entity product
        inner join catalog_product_entity_decimal p1 on product.entity_id = p1.entity_id and p1.attribute_id = {$list_attr['price']}";
    $items = $db->query($sql)->fetchAll();
    $list = array();
    foreach($items as $item){
        $listBySku[$item['sku']] = array(
            "sku" => $item['sku'],
            "entity_id" => $item['entity_id'],
            "price" => $item['price'],
            //"special_price" => $item['special_price'],
        );
        $listByEntity[$item['entity_id']] = array(
            "sku" => $item['sku'],
            "entity_id" => $item['entity_id'],
            "price" => $item['price'],
            //"special_price" => $item['special_price'],
        );
    }
    return array($listBySku,$listByEntity);
}

//Get all categories
function getAllMagentoCategories($db){
    $sql = "select distinct entity_id,value from catalog_category_entity_varchar where attribute_id=45;";
    $items = $db->query($sql)->fetchAll();
    $list = array();
    foreach($items as $item){
        $key = strtoupper(replaceCharacter($item['value']));
        $list[$key] = $item['entity_id'];
    }
    return $list;
}

function replaceCharacter($str){
    $unwanted_array = array(    'Š'=>'S', 'š'=>'s', 'Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
                            'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U',
                            'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss', 'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c',
                            'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o',
                            'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y','Ã³'=>'o' );
    return  strtr( $str, $unwanted_array );
}

function updateStock($db, $entity_id, $stock, $is_in_stock, $type=""){
    $upd_attr = "";
    if($type == 'llantas'){
        $upd_attr = ", enable_qty_increments = 1, qty_increments = 4";
    }
    $sql = "update cataloginventory_stock_item 
        set qty={$stock}, is_in_stock={$is_in_stock} {$upd_attr}
        where product_id ={$entity_id}";
    $update = $db->query($sql);
    $sql = "update cataloginventory_stock_status
        set qty={$stock}, stock_status={$is_in_stock}
        where product_id ={$entity_id}";
    $update = $db->query($sql);
    return $update->affectedRows();
}

function updateInventorySourceItem($db){
    $sql_insert = "INSERT IGNORE INTO inventory_source_item (source_code, sku, quantity, status)
                        select 'default', sku, qty, stock_status 
                    from (cataloginventory_stock_status as lg 
                    join catalog_product_entity as prd on((lg.product_id = prd.entity_id)))";
    $insert = $db->query($sql_insert);
    $affectedRows = $insert->affectedRows();

    $sql = "UPDATE inventory_source_item inv 
                inner join catalog_product_entity cat on inv.sku = cat.sku
                inner join cataloginventory_stock_status cat_stk on cat_stk.product_id = cat.entity_id
            set quantity = cat_stk.qty, status = cat_stk.stock_status";
    $update = $db->query($sql);
    return $update->affectedRows();
}

function updateStatus($db, $entity_id, $status, $list_attr){
    $sql = "update catalog_product_entity_int 
        set value = {$status}
        where attribute_id = {$list_attr['status']} and entity_id = {$entity_id};";
    $update = $db->query($sql);
    return $update->affectedRows();
}

function updatePrice($db, $entity_id, $price, $list_attr){
    $sql = "update catalog_product_entity_decimal 
        set value = {$price}
        where attribute_id = {$list_attr['price']} and entity_id = {$entity_id};";
    $update = $db->query($sql);
    return $update->affectedRows();
}

function insertPrice($db, $entity_id, $price, $list_attr){
    $sql = "insert into catalog_product_entity_decimal (attribute_id, store_id, entity_id, value)
            values ({$list_attr['price']}, 0, {$entity_id}, {$price}); ";
    $insert = $db->query($sql);
    return $insert->affectedRows();
}

function updateSpecialPrice($db, $entity_id, $special_price, $list_attr){
    $sql = "update catalog_product_entity_decimal 
        set value = {$special_price}
        where attribute_id = {$list_attr['special_price']} and entity_id = {$entity_id};";
    $update = $db->query($sql);
    return $update->affectedRows();
}

function updateProductAttrSelect($db, $entity_id, $attr_id, $value, $action, $type_attr = ""){
    $return = "";
    if(trim($value)){
        if($action == 'update'){ //tiene asignado otro valor
            //DELETE valor asignado
            $sql_delete = "DELETE from catalog_product_entity_int WHERE attribute_id = {$attr_id} and entity_id = {$entity_id};";
            $delete = $db->query($sql_delete); 
            $affectedRows = $delete->affectedRows();
            $return = "Delete catalog_product_entity_int: entity_id:". $entity_id." - attribute_id:".$attr_id." - affectedRows:".$affectedRows."\n";
            $action = 'insert';
        }
        if($action == 'insert'){
            $sql_value_exist = "select attr_op.option_id, attr_op.attribute_id, op_val.value, op_val.store_id  
                from 
                eav_attribute_option attr_op
                inner join eav_attribute_option_value op_val on attr_op.option_id = op_val.option_id
                where op_val.store_id = 0 and attr_op.attribute_id = {$attr_id} and op_val.value = '{$value}';";
            $item = $db->query($sql_value_exist)->fetchArray();
            if(count($item)){ //el valor existe
                $sql_delete = "DELETE from catalog_product_entity_int WHERE attribute_id = {$attr_id} and entity_id = {$entity_id};";
                $delete = $db->query($sql_delete);
                $sql_insert = "insert into catalog_product_entity_int 
                    (attribute_id, store_id, entity_id, value)
                    values 
                    ({$item['attribute_id']}, {$item['store_id']}, {$entity_id}, {$item['option_id']});";
                $insert = $db->query($sql_insert);
                $affectedRows = $insert->affectedRows();
                $return .= "Insert catalog_product_entity_int: entity_id:". $entity_id." - attribute_id:".$item['attribute_id']." - value:".$item['option_id']." - affectedRows:".$affectedRows."\n";
            }else{
                $sql_max_sort_order = "select max(sort_order) +1 as next_order 
                    from eav_attribute_option 
                    where attribute_id = {$attr_id}";
                $max_sort_order = $db->query($sql_max_sort_order)->fetchArray();

                $next_order = $max_sort_order['next_order'];
                if(!$max_sort_order['next_order']){
                    $next_order = 1;
                }
                $sql_insert = "insert into eav_attribute_option 
                    (attribute_id, sort_order)
                    values 
                    ({$attr_id}, {$next_order});";
                $insert = $db->query($sql_insert);
                $affectedRows = $insert->affectedRows();
                $return .= "Insert eav_attribute_option: attr_id:". $attr_id." - sort_order:".$max_sort_order['next_order']." - affectedRows:".$affectedRows."\n";
                $option_id = $db->lastInsertID();
                if($type_attr == "mgs_brand"){
                    $sql_insert ="insert into mgs_brand (name, url_key, meta_keywords, meta_description, option_id)
                    values('{$value}', '{$value}', '{$value}', '{$value}', {$option_id});";
                    echo $sql_insert;
                    exit();
                    $insert = $db->query($sql_insert);
                    $return .= "Insert into MGS_BRAND: ".$value."\n";
                }
    
                $sql_insert = "insert into eav_attribute_option_value 
                    (option_id, store_id, value)
                    values 
                    ({$option_id}, 0, '{$value}');";
                $_id = $db->lastInsertID();
                $insert = $db->query($sql_insert);
                $affectedRows = $insert->affectedRows();
                $return .= "Insert eav_attribute_option_value: entity_id:". $entity_id." - option_id:".$option_id." - value:".$value." - affectedRows:".$affectedRows."\n";
                $sql_delete = "DELETE from catalog_product_entity_int WHERE attribute_id = {$attr_id} and entity_id = {$entity_id};";
                $delete = $db->query($sql_delete); 
                $sql_insert = "insert into catalog_product_entity_int 
                    (attribute_id, store_id, entity_id, value)
                    values 
                    ({$attr_id}, 0, {$entity_id}, {$option_id});";
                $insert = $db->query($sql_insert);
                $affectedRows = $insert->affectedRows();
                
                $return .= "Insert catalog_product_entity_int: entity_id:". $entity_id." - attribute_id:".$attr_id." - value:".$value." - affectedRows:".$affectedRows."\n";
            }
        }    
    }
    return $return;
}

function updateProductAttVarchar($db, $entity_id, $attr_id, $value, $action){
    if($action == "update"){
        $sql_insert = "update catalog_product_entity_varchar set value = '{$value}'
                where attribute_id = {$attr_id} and  entity_id = {$entity_id};";
        $insert = $db->query($sql_insert);
        $affectedRows = $insert->affectedRows();
        $return = "Update catalog_product_entity_varchar: entity_id:". $entity_id." - attribute_id:".$attr_id." - value:".$value." - affectedRows:".$affectedRows."\n" ;
    }else{
        $sql_delete = "DELETE from catalog_product_entity_varchar WHERE attribute_id = {$attr_id} and entity_id = {$entity_id};";
        $delete = $db->query($sql_delete); 
        $affectedRows = $delete->affectedRows();
        $return = "Delete option: entity_id:". $entity_id." - attribute_id:".$attr_id." - affectedRows:".$affectedRows."\n";
        $sql_insert = "insert into catalog_product_entity_varchar 
                (attribute_id, store_id, entity_id, value)
                values 
                ({$attr_id}, 0, {$entity_id}, '{$value}');";
        $insert = $db->query($sql_insert);
        $affectedRows = $insert->affectedRows();
        $return .= "Insert option: entity_id:". $entity_id." - attribute_id:".$attr_id." - value:".$value." - affectedRows:".$affectedRows."\n";
    }
    return $return;
}

function updateProductAttMultiSelect($db, $entity_id, $attr_id, $values, $action){
    $return = "";
    $elements = array();
    foreach($values as $value){
        $sql_value_exist = "select attr_op.option_id, attr_op.attribute_id, op_val.value, op_val.store_id  
        from 
        eav_attribute_option attr_op
        inner join eav_attribute_option_value op_val on attr_op.option_id = op_val.option_id
        where op_val.store_id = 0 and attr_op.attribute_id = {$attr_id} and op_val.value = '{$value}';";
        $item = $db->query($sql_value_exist)->fetchArray();
        if(count($item)){ //el valor existe
            $elements[] = $item["option_id"];
        }else{
            $sql_max_sort_order = "select max(sort_order) +1 as next_order 
                from eav_attribute_option 
                where attribute_id = {$attr_id}";
            $max_sort_order = $db->query($sql_max_sort_order)->fetchArray();

            $sql_insert = "insert into eav_attribute_option 
                (attribute_id, sort_order)
                values 
                ({$attr_id}, {$max_sort_order['next_order']});";
            $insert = $db->query($sql_insert);
            $affectedRows = $insert->affectedRows();
            $return .= "Insert eav_attribute_option: attr_id:". $attr_id." - sort_order:".$max_sort_order['next_order']." - affectedRows:".$affectedRows."\n";
            $option_id = $db->lastInsertID();

            $sql_insert = "insert into eav_attribute_option_value 
                (option_id, store_id, value)
                values 
                ({$option_id}, 0, '{$value}');";
            $_id = $db->lastInsertID();
            $insert = $db->query($sql_insert);
            $affectedRows = $insert->affectedRows();
            $return .= "Insert eav_attribute_option_value: entity_id:". $entity_id." - option_id:".$option_id." - value:".$value." - affectedRows:".$affectedRows."\n";
            $elements[] = $option_id;
        }
    }
    $value = implode(",", $elements);
    if($action == "update"){
        $sql_insert = "update catalog_product_entity_varchar set value = '{$value}'
                where attribute_id = {$attr_id} and  entity_id = {$entity_id};";
        $insert = $db->query($sql_insert);
        $affectedRows = $insert->affectedRows();
        $return = "Update catalog_product_entity_varchar: entity_id:". $entity_id." - attribute_id:".$attr_id." - value:".$value." - affectedRows:".$affectedRows."\n" ;
    }else{
        $sql_delete = "DELETE from catalog_product_entity_varchar WHERE attribute_id = {$attr_id} and entity_id = {$entity_id};";
        $delete = $db->query($sql_delete); 
        $affectedRows = $delete->affectedRows();
        $return = "Delete option: entity_id:". $entity_id." - attribute_id:".$attr_id." - affectedRows:".$affectedRows."\n";
        $sql_insert = "insert into catalog_product_entity_varchar 
                (attribute_id, store_id, entity_id, value)
                values 
                ({$attr_id}, 0, {$entity_id}, '{$value}');";
        $insert = $db->query($sql_insert);
        $affectedRows = $insert->affectedRows();
        $return .= "Insert option: entity_id:". $entity_id." - attribute_id:".$attr_id." - value:".$value." - affectedRows:".$affectedRows."\n";
    }
    
    return $return;
}

function updateProductAttText($db, $entity_id, $attr_id, $value, $action){
    if($action == "update"){
        $sql_insert = "update catalog_product_entity_text set value = '{$value}'
                where attribute_id = {$attr_id} and  entity_id = {$entity_id};";
        $insert = $db->query($sql_insert);
        $affectedRows = $insert->affectedRows();
        $return = "Update catalog_product_entity_text: entity_id:". $entity_id." - attribute_id:".$attr_id." - value:".$value." - affectedRows:".$affectedRows."\n" ;
    }else{
        $sql_delete = "DELETE from catalog_product_entity_text WHERE attribute_id = {$attr_id} and entity_id = {$entity_id};";
        $delete = $db->query($sql_delete); 
        $affectedRows = $delete->affectedRows();
        $return = "Delete option: entity_id:". $entity_id." - attribute_id:".$attr_id." - affectedRows:".$affectedRows."\n";
        $sql_insert = "insert into catalog_product_entity_text 
                (attribute_id, store_id, entity_id, value)
                values 
                ({$attr_id}, 0, {$entity_id}, '{$value}');";
        $insert = $db->query($sql_insert);
        $affectedRows = $insert->affectedRows();
        $return .= "Insert option: entity_id:". $entity_id." - attribute_id:".$attr_id." - value:".$value." - affectedRows:".$affectedRows."\n";
    }
    return $return;
}

function updateProductAttDecimal($db, $entity_id, $value, $attr_id){
    $sql = "update catalog_product_entity_decimal 
        set value = {$value}
        where attribute_id = {$attr_id} and entity_id = {$entity_id};";
    $update = $db->query($sql);
    return $update->affectedRows();
}

function checkProductCategory($db, $product, $list_attr_value){
    $sql_category = "select entity_id from catalog_category_entity_varchar
        where value like '{$product['NombreSubCategoria']}' and attribute_id in (
            select attribute_id from eav_attribute where attribute_code = 'name'
        ) and store_id = 0 limit 1";
    $category_id = $db->query($sql_category)->fetchArray();
    if(count($category_id)){
        $sql_value_exist = "select * from catalog_category_product
            where product_id = {$list_attr_value['entity_id']} and category_id = {$category_id['entity_id']}
            limit 1";
        $item = $db->query($sql_value_exist)->fetchArray();
        if(!count($item)){
            $sql_insert = "insert into catalog_category_product (category_id, product_id, position)
            values({$category_id['entity_id']}, {$list_attr_value['entity_id']}, 0)";
            $insert = $db->query($sql_insert);
            $affectedRows = $insert->affectedRows();
            return "Insert catalog_category_product: product sku:". $list_attr_value["sku"]." - category_id:".$category_id['entity_id']." - affectedRows:".$affectedRows."\n";
        }
    }else{
        return "No existe la categoria: product sku:". $list_attr_value["sku"]." - category_name:".$product['NombreSubCategoria']."\n";
    }
}

function getAllMagentoProductMgsBrand($db){
    $sql = "select p.sku, p.entity_id, b.brand_id 
    from  mgs_brand_product b
    inner join catalog_product_entity p on b.product_id = p.entity_id;";
    $items = $db->query($sql)->fetchAll();
    $list = array();
    foreach($items as $item){
        if(key_exists($item['sku'], $list)){
            $list[$item['sku']]["brand_ids"][$item['brand_id']] = $item['brand_id'];
        }else{
            $list[$item['sku']] = array(
                "sku" => $item['sku'],
                "entity_id" => $item['entity_id'],
                "brand_ids" => array($item['brand_id']=>$item['brand_id']),
            );
        }
    }
    return $list;
}

function getAllMagentoMgsBrand($db){
    $sql = "select brand_id, name from mgs_brand;";
    $items = $db->query($sql)->fetchAll();
    $list = array();
    foreach($items as $item){
        $list[strtoupper($item['name'])] = array(
            "brand_id" => $item['brand_id'],
            "name" => $item['name'],
        );
    }
    return $list;
}

function insertProductMgsBrand($db, $brand_id, $entity_id){
    $sql_insert ="insert into mgs_brand_product (brand_id, product_id, position) values({$brand_id}, {$entity_id}, 0);";
    $insert = $db->query($sql_insert);
    $affectedRows = $insert->affectedRows();
    return "Insert mgs_brand_product: entity_id:". $entity_id." - brand_id:".$brand_id." - affectedRows:".$affectedRows."\n";
}

function updateProductMgsBrand($db, $brand_id, $entity_id){
    $sql = "update mgs_brand_product set brand_id = {$brand_id} where product_id = {$entity_id};";
    $update = $db->query($sql);
    $affectedRows = $update->affectedRows();
    return "Update mgs_brand_product: entity_id:". $entity_id." - brand_id:".$brand_id." - affectedRows:".$affectedRows."\n";
}

function insertMgsBrand($db, $brand_name, $option_id){
    $brand_name = strtoupper($brand_name);
    $sql_insert ="insert into mgs_brand (name, url_key, meta_keywords, meta_description, option_id)
    values('{$brand_name}', '{$brand_name}', '{$brand_name}', '{$brand_name}', {$option_id});";
    $insert = $db->query($sql_insert);
    return $db->lastInsertID();
}
?>