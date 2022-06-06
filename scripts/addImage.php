<?php 


use Magento\Framework\App\Bootstrap;

require __DIR__ . '/../app/bootstrap.php';


function logger($code,$msg, $file_name = 'addImage_'){
    $log = "[execution - ".date("Y-m-d H:i:s")."]: Code:".$code." // ".$msg.PHP_EOL;
    file_put_contents('./log/'.$file_name.date("Y-m-d").'.log', $log, FILE_APPEND);
}

$bootstrap = Bootstrap::create(BP, $_SERVER);
$objectManager = $bootstrap->getObjectManager();

$state = $objectManager->get('Magento\Framework\App\State');
$state->setAreaCode('adminhtml');

$galleryReadHandler = $objectManager->create('Magento\Catalog\Model\Product\Gallery\ReadHandler');
$imageProcessor = $objectManager->create('Magento\Catalog\Model\Product\Gallery\Processor');
$productGallery = $objectManager->create('Magento\Catalog\Model\ResourceModel\Product\Gallery');

$cant = $cant_add = $cant_error = $cant_del = 0;
logger('Init add images'," ---- ".date("Y-m-d H:i:s"));
//$img_faltantes = array();
foreach (glob(__DIR__ . "/../pub/media/upload_images/*.{jpg}", GLOB_BRACE) as $image) {
    $imageFileName = trim(pathinfo($image)['filename']);
    $sku = $imageFileName;
    //$list_sku = array("T-04491-1229B-3");
    $pos = strpos($sku, "+");
    if ($pos!== false) {
        $sku = substr($sku, 0, -2);
    }
    try {
        //if (in_array($sku, $img_faltantes)):
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
        //endif;
    } catch (\Exception $e) {
        echo "ERROR -------------- ". $sku ."  --------------\n";
        echo $e->getMessage();
        echo "\n";
    }
}
foreach (glob(__DIR__ . "/../pub/media/upload_images/*.{jpg}", GLOB_BRACE) as $image) {
    $cant++;
    $imageFileName = trim(pathinfo($image)['filename']);
    $sku = $imageFileName;
    $pos = strpos($sku, "+");
    if ($pos !== false) {
        $sku = substr($sku, 0, -2);
    }
    try {
        if (in_array($sku, $img_faltantes)):
        $product = $objectManager->create('Magento\Catalog\Model\Product')->loadByAttribute('sku', $sku);
        if ($product) {
            /**
             * Add image. Image directory must be in ROOT/pub/media for addImageToMediaGallery() method to work
             */
            $product->addImageToMediaGallery('upload_images' . DIRECTORY_SEPARATOR . pathinfo($image)['basename'], array('image', 'small_image', 'thumbnail',''), false, false);
            $product->save();
            echo "Added media image for {$sku}" . "\n";
            logger('Img ADD ', $sku );
            $cant_add++;
        }else{
            echo " -- SKU NO EXISTE:".$sku."\n";
            logger('SKU NO EXISTE ', $sku );
            $cant_error++;
        }
        endif;
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