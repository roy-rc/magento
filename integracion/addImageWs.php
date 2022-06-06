<?php 

require_once('functions.php');
require_once('db.php');

use Magento\Framework\App\Bootstrap;

require __DIR__ . '/../app/bootstrap.php';

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

$state = $objectManager->get('Magento\Framework\App\State');
$state->setAreaCode('adminhtml');

$galleryReadHandler = $objectManager->create('Magento\Catalog\Model\Product\Gallery\ReadHandler');
$imageProcessor = $objectManager->create('Magento\Catalog\Model\Product\Gallery\Processor');
$productGallery = $objectManager->create('Magento\Catalog\Model\ResourceModel\Product\Gallery');

//Get Products from SAP
$categories = getAllCategories();
foreach($categories["Response"] as $category){
    echo "Category: ".$category["CodigoSubCategoria"]."\n";
    //if($category["CodigoSubCategoria"] == 10601):
        $sap_products_ws = getAllProductsByCategory($category["CodigoSubCategoria"]);
        //productos
        foreach($sap_products_ws["Imagenes"] as $sap_prod){
            $sap_products[$sap_prod["SKU"]][] =  $sap_prod["Imagen"];
            $sap_products[$sap_prod["SKU"]][] =  $sap_prod["Imagen2"];
            $sap_products[$sap_prod["SKU"]][] =  $sap_prod["Imagen3"];
            $sap_products[$sap_prod["SKU"]][] =  $sap_prod["Imagen4"];
            $sap_products[$sap_prod["SKU"]][] =  $sap_prod["Imagen5"];
            $sap_products[$sap_prod["SKU"]][] =  $sap_prod["Imagen6"];
        }
    //endif;
}
echo "Cant elem sap:".count($sap_products)."\n";

//get Magento products
$productCollection = $objectManager->create('Magento\Catalog\Model\ResourceModel\Product\CollectionFactory');
$collection = $productCollection->create()
            ->addAttributeToSelect(['id','sku','name','status'])
            ->addMediaGalleryData()
            ->load();
echo "cant magento products: " . count($collection)."\n";
$list_mg_products = array();

foreach ($collection as $product) {
    $arr_img = array("-","-","-","-");
    $i=0;
    foreach($product->getMediaGalleryImages() as $imagen){
        $arr_img[$i++] =$imagen['url'];
    }
    if($arr_img[0] == "-"){
        //Product without image
        if(key_exists($product->getSku(), $sap_products)){
            foreach($sap_products[$product->getSku()] as $sap_image_name){
                $nombre_fichero = __DIR__ . "/../pub/media/upload_images/{$sap_image_name}";
                if (file_exists($nombre_fichero)) {
                    $list_mg_products[$product->getSku()][] = $sap_image_name;
                } else {
                    $pos = strpos($nombre_fichero, "SIN IMAGEN");
                    if ($pos === false) {
                        echo "SKU: " . $product->getSku()."\n";
                        echo "El fichero $nombre_fichero no existe\n";
                    }
                }
            }
        }
    }
}

echo "Cant prod no Img: " . count( $list_mg_products )."\n";

$cant = $cant_add = $cant_error = $cant_del = 0;
logger('Init add images'," ---- ".date("Y-m-d H:i:s"));

foreach($list_mg_products as $sku => $product){

    try {
        $product = $objectManager->create('Magento\Catalog\Model\Product')->loadByAttribute('sku', $sku);
        if ($product) {
            $galleryReadHandler->execute($product);

            // Unset existing images
            $images = $product->getMediaGalleryImages();
            foreach($images as $child) {
                $cant_del++;
                $productGallery->deleteGallery($child->getValueId());
                $imageProcessor->removeImage($product, $child->getFile());
            }
        }else{
            echo " -- SKU NO EXISTE:".$sku."\n";
            $cant_error++;
        }

        $product = $objectManager->create('Magento\Catalog\Model\Product')->loadByAttribute('sku', $sku);
        if ($product) {
            /**
             * Add image. Image directory must be in ROOT/pub/media for addImageToMediaGallery() method to work
             */
            if(key_exists($sku, $list_mg_products)){
                foreach($list_mg_products[$sku] as $item){
                    $product->addImageToMediaGallery('upload_images' . DIRECTORY_SEPARATOR . pathinfo($item)['basename'], array('image', 'small_image', 'thumbnail',''), false, false);
                    $product->save();
                    echo "Added media image for {$sku}" . "\n";
                    logger('Img ADD ', $sku );
                    $cant_add++;
                }
                
            }else{
                echo " -- SKU NO EXISTE en Array de imagenes:".$sku."\n";
                logger('SKU NO EXISTE en Array de imagenes ', $sku );
                $cant_error++;
            }
        }else{
            echo " -- SKU NO EXISTE:".$sku."\n";
            logger('SKU NO EXISTE ', $sku );
            $cant_error++;
        }

    } catch (\Exception $e) {
        echo "ERROR -------------- ". $sku ."  --------------\n";
        logger('ERROR ', $sku . " --- " .$e->getMessage());
        echo $e->getMessage();
        echo "\n";
    }
}
logger('END add images'," ---- ".date("Y-m-d H:i:s"));

echo "cant: ".$cant."\n";
echo "cant add: ".$cant_add."\n";
echo "cant del: ".$cant_del."\n";
echo "cant error: ".$cant_error."\n";  

?>