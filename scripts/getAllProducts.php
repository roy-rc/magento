<?php
use Magento\Framework\App\Bootstrap;
require '../app/bootstrap.php';
$bootstrap = Bootstrap::create(BP, $_SERVER);


$objectManager = $bootstrap->getObjectManager();
$appState = $objectManager->get('\Magento\Framework\App\State');
//$appState->setAreaCode('frontend');
$appState->setAreaCode('adminhtml');
$productCollection = $objectManager->create('Magento\Catalog\Model\ResourceModel\Product\CollectionFactory');

//$collection = $productCollection->create()
//        ->addAttributeToSelect('*')
//      ->addAttributeToFilter('status',\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
//        ->addAttributeToFilter('sku',array('in' => array('51598')))
//        ->load();

$productCollection = $objectManager->create('Magento\Catalog\Model\ResourceModel\Product\CollectionFactory');

$collection = $productCollection->create()
            ->addAttributeToSelect(['id','sku','name','status'])
            ->addAttributeToFilter('type_id',array('eq' => 'simple'))
            ->addMediaGalleryData()
            ->load();
echo count($collection)."<br>";

foreach ($collection as $product) {
    echo  $product->getSku()."\n";
    $product->setVisibility(4); 
    $url = preg_replace('#[^0-9a-z]+#i', '-', $product->getName().'-'.$product->getSku());
    $url = strtolower($url);
    $product->setUrlKey($url);
    $product->save();
}
?>