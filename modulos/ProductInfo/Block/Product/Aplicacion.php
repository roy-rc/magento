<?php
/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Customcode\ProductInfo\Block\Product;

class Aplicacion extends \Magento\Framework\View\Element\Template
{
    /**
     * Constructor
     *
     * @param \Magento\Framework\View\Element\Template\Context  $context
     * @param array $data
     */
    protected $_registry;
    
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        array $data = []
    ) {
        $this->_registry = $registry;
        parent::__construct($context, $data);
    }

    /**
     * @return string
     */
    public function showAplicacion()
    {
        $product =  $this->getCurrentProduct();
        return $this->getAplicaciones($product->getOem());
        //Your block code
        //return __('Hello Developer! This how to get the storename: %1 and this is the way to build a url: %2', $this->_storeManager->getStore()->getName(), $this->getUrl('contacts'));
    }

    public function getCurrentProduct()
    {
        //$productId = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Catalog\Model\Session')->getData('last_viewed_product_id');
        //$product = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Catalog\Model\Product')->load($productId);
        $product =  $this->_registry->registry('current_product');
        return $product;
    }

    private function getAplicaciones($oem){
        $fecha = date("Ymd");
        $url = "http://201.238.200.3:8000/WS/services/item/getArticuloB2CT.xsjs?id={$oem}&fecha={$fecha}";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL,$url);
        if (($result = curl_exec($ch)) === FALSE) {
            die();
        } 
        curl_close($ch);
        $result = str_replace("\n", '', str_replace("\r", '', $result) );
        //$result = str_replace('"CodigoFabricante": "S/F",', '"CodigoFabricante": "S/F"', $result );
        $result = json_decode($result, true); 
        switch(json_last_error()) {
            case JSON_ERROR_NONE:
                if($result["ResponseStatus"] === "Error"){
                    return array("Response"=>array());
                }else{
                    return $result["Response"];
                }
            break;
            case JSON_ERROR_DEPTH:
                echo ' - Excedido tamaño máximo de la pila';
                return array("Response"=>array());
                
            break;
            case JSON_ERROR_STATE_MISMATCH:
                echo ' - Desbordamiento de buffer o los modos no coinciden';
                return array("Response"=>array());
            break;
            case JSON_ERROR_CTRL_CHAR:
                echo ' - Encontrado carácter de control no esperado';
                return array("Response"=>array());
            break;
            case JSON_ERROR_SYNTAX:
                echo ' - Error de sintaxis, JSON mal formado';
                return array("Response"=>array());
            break;
            case JSON_ERROR_UTF8:
                echo ' - Caracteres UTF-8 malformados, posiblemente codificados de forma incorrecta';
                return array("Response"=>array());
            break;
            default:
                echo ' - Error desconocido';
                return array("Response"=>array());
            break;
        }
    }
}

