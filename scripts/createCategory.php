<?php 
use Magento\Framework\App\Bootstrap;
require '../app/bootstrap.php';
$bootstrap = Bootstrap::create(BP, $_SERVER);

$objectManager = $bootstrap->getObjectManager();

$cats = [
    "Accesorios","Carrocería","Dirección","Distribución","Eléctricos","Frenos","Motor","Lubricantes",
    "Suspensión","Transmisión","Encendido","Afinamiento","Refrigeración y Calefacción","Sistema Escape"
];


$cats = array(
    "Accesorios"=>array(
       "Limpieza y Cuidado",
       "Seguridad",
       "Alarmas",
       "Gatas y Herramientas",
       "Acc. Baterias",
       "Autos a escala"),
    "Carrocería"=>array(
       "ABSORVEDOR IMPACTO",
       "CAPOT",
       "PARACHOQUES",
       "Opticos y Faroles",
       "PANELES Y PUERTAS",
       "TAPABARROS",
       "MASCARAS Y MOLDURAS",
       "Frontales",
       "Espejos",
       "Ampolletas",
       "Antenas",
       "Chapas de puerta y maleta",
       "Guardafangos",
       "Limpia parabrisas",
       "Neblineros",
       "Rejillas",
       "Vidrios"),
    "Dirección"=>array(
       "CREMALLERAS",
       "Terminales de Direccion",
       "Bomba Direccion",
       "BARRAS DE DIRECCIÓN",
       "BUJES DIRECCION"),
    "Distribución"=>array(
       "KIT DISTRIBUCIÓN",
       "Piñones de Distribucion",
       "EJE De LEVAS",
       "Tensores y Guias",
       "Cadenas y Correas",
       "Tapa Distribucion",
       "Retenes de Distribucion"),
    "Eléctricos"=>array(
       "CAJA REGULADORAS",
       "SENSORES",
       "Fusibles",
       "Alternadores y Partes",
       "Telecomandos",
       "Relays",
       "Motores de Partida",
       "Switchs",
       "Cinta Air bag",
       "Chapa de contacto",
       "Soquetes",
       "Fusible"),
    "Frenos"=>array(
       "BOMBA DE FRENO",
       "BALATAS Y PATINES",
       "CILINDROS DE FRENO",
       "PASTILLAS",
       "CABLES DE FRENO",
       "Disco de Freno y Tambores",
       "Kit Reparaciones",
       "Flexibles y Calipers"),
    "Motor"=>array(
       "BOMBA ACEITE",
       "CARTER",
       "MULTIPLES",
       "TURBOS",
       "EMPAQUETADURAS",
       "RETENES DE MOTOR",
       "SOPORTES MOTOR",
       "Pistones y Anillos",
       "Metales",
       "Culatas",
       "Valvulas de Motor",
       "Bielas y Cigüeñal",
       "Block",
       "Pernos y Golillas",
       "Sellos",
       "Polea Cigueñal",
       "Cable acelerador",
       "Taquies"),
    "Lubricantes"=>array(
       "SILICONAS",
       "GRASAS",
       "Aceites de Motor",
       "Aceites de Transmision",
       "Aditivos y Refrigerantes",
       "Liquido de Freno"),
    "Suspensión"=>array(
       "BANDEJAS",
       "BARRAS Y BRAZOS",
       "BIELETAS",
       "BUJES Y GOMAS",
       "ROTULAS",
       "AMORTIGUADORES",
       "Espirales y Cazoletas",
       "Paquetes de Resorte"),
    "Transmisión"=>array(
       "BOMBA EMBRAGUE",
       "Embrague y Volantes",
       "CARDAN Y CRUCETAS",
       "HOMOCINETICAS",
       "CAJAS DE CAMBIOS Y PARTES",
       "CABLES EMBRAGUES",
       "Mazas",
       "Muñones",
       "Diferencial",
       "Rodamientos",
       "Cilindros Maestros",
       "Reten de Rueda",
       "Cables de transmision",
       "Piñon y corona"),
    "Encendido"=>array(
       "BOBINAS",
       "CABLES DE BUJIAS",
       "Distribuidores",
       "Bomba De Bencina",
       "Valvulas y Sensores",
       "Flujometro",
       "Inyectores y Bombas"),
    "Afinamiento"=>array(
       "CORREAS",
       "FILTRO DE ACEITE",
       "FILTRO DE COMBUSTIBLE",
       "Filtro Cabina o Polen",
       "Bujias",
       "FILTRO DE AIRE"),
    "Refrigeración y Calefacción"=>array(
       "Radiadores e Intercooler",
       "Magueras y Tubos",
       "Tapas y Termostatos",
       "Depositos y Estanques",
       "Centrifugos y Acoples",
       "BOMBA DE AGUA"),
    "Sistema Escape"=>array(
       "FLEXIBLES",
       "Silenciadores",
       "Catalizadores",
       "Empaquetadura Escape")
    );

$state = $objectManager->get('Magento\Framework\App\State');
$state->setAreaCode('adminhtml');

//get category factory
$categoryCollectionFactory = $objectManager->create('\Magento\Catalog\Model\ResourceModel\Category\CollectionFactory');
$categoryCollection = $categoryCollectionFactory->create();
$categoryCollection->addAttributeToSelect('*');

$categoryArray = array();

foreach ($categoryCollection as $category) {
    if($category->getLevel() == 2)
        $categoryArray[$category->getName()] = $category->getId();
}

foreach($cats as $key=> $items) {
    $parent_id = $categoryArray[$key];
    $position = 1;
    foreach($items as $item){
        $data = [
            'data' => [
                "parent_id" => $parent_id,
                'name' => $item,
                "is_active" => true,
                "position" => $position++,
                "include_in_menu" => false,
            ]
    
        ];
        $category = $objectManager ->create('Magento\Catalog\Model\Category', $data);
        $repository = $objectManager->get(\Magento\Catalog\Api\CategoryRepositoryInterface::class);
        $result = $repository->save($category);
    }
    
}



?>

 
