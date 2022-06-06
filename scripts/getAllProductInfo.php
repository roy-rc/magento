<?php
use Magento\Framework\App\Bootstrap;
require __DIR__ . '/../app/bootstrap.php';
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
            //->addAttributeToFilter('type_id',array('eq' => 'configurable'))
            ->addMediaGalleryData()
            ->load();
echo count($collection)."<br>";

$fp = fopen('data.csv', 'w');
$line = array("n","id","sku","name","short_desc","status","price","special_price","stock","is_in_stock","img_1","img_2","img_3","img_4","category_1","category_2");
fputcsv($fp, $line);

$stockState = $objectManager->get('\Magento\CatalogInventory\Api\StockStateInterface');
$stockRegistry = $objectManager->get('Magento\CatalogInventory\Api\StockRegistryInterface');

$cant = 1;
foreach ($collection as $product) {
    echo  $product->getId()." - ".$product->getSku()."\n"; 
    $arr_img = $arr_cat = array();

    $productCategoryIds = $product->getCategoryIds();
    $categories = array();
    if(count($productCategoryIds)){
        $categoryCollection = $objectManager->create('Magento\Catalog\Model\ResourceModel\Category\CollectionFactory');
        $categories = $categoryCollection->create()
                            ->addAttributeToSelect(['id','name'])
                            ->addAttributeToFilter('entity_id', $productCategoryIds);
    }
    
    if ($categories && count($categories) > 0) {
        foreach ($categories as $category) {
            $arr_cat[] = $category->getName();
        }
    }
    $arr_img = array("-","-","-","-");
    $i=0;
    foreach($product->getMediaGalleryImages() as $imagen){
        $arr_img[$i++] =$imagen['url'];
    }

    $itemStock = $stockState->getStockQty($product->getId(), $product->getStore()->getWebsiteId());
    $stockItem = $stockRegistry->getStockItem($product->getId());
    $IsInStock = $stockItem->getIsInStock()?1:0;
    $price = $product->getPriceInfo()->getPrice('regular_price')->getValue();
    $spacial_price = $product->getPriceInfo()->getPrice('special_price')->getValue();
    
    $values = array($cant++ ,
                    $product->getId(), 
                    $product->getSku(),
                    $product->getName(), 
                    $product->getShortDescription(), 
                    $product->getStatus(),
                    $price,
                    $spacial_price,
                    $itemStock,
                    $IsInStock);
    fputcsv($fp, array_merge($values, $arr_img, $arr_cat));
}


fclose($fp);
exit();
///////////////////////////////////////////////////////////////////////////////////////
$stockState = $objectManager->get('\Magento\CatalogInventory\Api\StockStateInterface');
$stockRegistry = $objectManager->get('Magento\CatalogInventory\Api\StockRegistryInterface');
echo "id, sku, name, type, status, stock_state_qty, stock_registry_qty, is_in_stock\n<br>";
$cant = 1;

foreach ($collection as $product){
    /*$productTypeInstance = $product->getTypeInstance();
    $usedProducts = $productTypeInstance->getUsedProducts($product);*/
    $itemStock = $stockState->getStockQty($product->getId(), $product->getStore()->getWebsiteId());
    $stockItem = $stockRegistry->getStockItem($product->getId());
    $IsInStock = $stockItem->getIsInStock()?1:0;
    if ($product->getTypeId() == "configurable"){    
	    $list[] = $product->getSku();
	    //        echo $cant++.' -- , '.$product->getId().', ' .$product->getSku().','.$product->getName().', '.$product->getTypeId().', '.$product->getStatus().', '.$product->getPrice().', '.$product->getSpecialPrice().', '.$itemStock.', '.$stockItem->getQty().', '.$IsInStock."\n<br>";
	$configProduct = $objectManager->create('Magento\Catalog\Model\Product')->load($product->getId());
        $children= $configProduct->getTypeInstance()->getUsedProducts($configProduct);
        foreach ($children as $child){
        //  echo " >>>> ".$child->getId().$child->getSku().$child->getName()."\n<br>";
	    $itemStock = $stockState->getStockQty($child->getId(), $child->getStore()->getWebsiteId());
            $stockItem = $stockRegistry->getStockItem($child->getId());
	    $IsInStock = $stockItem->getIsInStock()?1:0;
	    $list[] = $child->getSku();

//            echo " >>>> ".$cant++. ' - ' .$child->getId().', ' .$child->getSku().", ".$child->getName().", ".$child->getTypeId().', '.$child->getStatus().', '.(int)$child->getPrice().', '.(int)$child->getSpecialPrice().', '.$itemStock.', '.$stockItem->getQty().', '.$IsInStock."\n<br>";
        }
    }
    /*foreach ($usedProducts  as $child) {
        $itemStock = $stockState->getStockQty($child->getId(), $child->getStore()->getWebsiteId());
        $stockItem = $stockRegistry->getStockItem($child->getId());
        $IsInStock = $stockItem->getIsInStock()?1:0;
        echo $child->getId().', ' .$child->getSku().", ".$child->getName().", ".$child->getTypeId().', '.$child->getStatus().', '.(int)$child->getPric
e().', '.(int)$child->getSpecialPrice().', '.$itemStock.', '.$stockItem->getQty().', '.$IsInStock."\n<br>"; 
    }
    echo "-----------------\n<br>";*/
}
$cant = 1;
echo count($list);
foreach ($collection as $product){
    /*$productTypeInstance = $product->getTypeInstance();
    $usedProducts = $productTypeInstance->getUsedProducts($product);*/
    $itemStock = $stockState->getStockQty($product->getId(), $product->getStore()->getWebsiteId());
    $stockItem = $stockRegistry->getStockItem($product->getId());
    $IsInStock = $stockItem->getIsInStock()?1:0;
    if(!in_array($product->getSku(), $list)){
    	echo $cant++ ." - ".$product->getSku()." - ".$product->getTypeId()."<br>";
    }
}

?>