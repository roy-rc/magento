<?php 
use Magento\Framework\App\Bootstrap;
require '../app/bootstrap.php';
$bootstrap = Bootstrap::create(BP, $_SERVER);

$objectManager = $bootstrap->getObjectManager();

$state = $objectManager->get('Magento\Framework\App\State');
$state->setAreaCode('adminhtml');

//get category factory
$categoryCollectionFactory = $objectManager->create('\Magento\Catalog\Model\ResourceModel\Category\CollectionFactory');
$categoryCollection = $categoryCollectionFactory->create();
$categoryCollection->addAttributeToSelect('*');

$categoryArray = array();

foreach ($categoryCollection as $category) {

   if($category->getLevel() > 1){
      /* if($category->getId() == 7){
         echo $category->getId()."  ".$category->getName()."  ".$category->getUrlKey()."  ".$category->getLevel()."\n";
         var_dump($category->getData());
      } */
      
      //if($category->getPath() != 6){
         $category_obj = $objectManager->create('Magento\Catalog\Model\Category')->load($category->getId());
         $category_obj->setUrlKey($category->getUrlKey()."-por-mayor");
         $category_obj->save();
      //}
   }
}


?>

 
