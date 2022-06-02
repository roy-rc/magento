<?php

namespace Customcode\Ctacorriente\Cron;

class Customers
{
    protected $logger;
    protected $resourceConnection;
    protected $helper;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Customcode\Ctacorriente\Helper\Data $helper
    ) {
        $this->logger = $logger;
        $this->resourceConnection = $resourceConnection;
        $this->helper = $helper;
    }

    public function execute()
    {
        $table = 'ctacorriente_customers';
        $db = $this->resourceConnection->getConnection('core_write');
        $table = $this->resourceConnection->getTableName($table);
        $db->query("CREATE TABLE IF NOT EXISTS $table (
            codigo VARCHAR(20) PRIMARY KEY,
            nombres VARCHAR(40),
            apellidos VARCHAR(40),
            rut VARCHAR(13) NOT NULL,
            correo VARCHAR(60),
            telefono VARCHAR(40),
            celular VARCHAR(30),
            organismo VARCHAR(100),
            unidad VARCHAR(100),
            rut_unidad VARCHAR(13) NOT NULL,
            direccion VARCHAR(100),
            cit_name VARCHAR(60),
            dis_name VARCHAR(60)
            )"
        );
        
        $response = $this->helper->getCtacorrienteCustomers();
        $this->logger->info('Save Ctacorriente Customers - INICIADO');

        if (isset($response['Cantidad']) && isset($response['UsuariosCtacorriente']) && (int)$response['Cantidad'] > 1000) {
            foreach ($response['UsuariosCtacorriente'] as $user) {
                $query = 'REPLACE INTO ' . $table . ' VALUES(
                    "' . $user['Codigo'] . '",
                    "' . $user['Nombres'] . '",
                    "' . $user['Apellidos'] . '",
                    "' . $user['Rut'] . '",
                    "' . $user['Correo'] . '",
                    "' . $user['Telefono'] . '",
                    "' . $user['Celular'] . '",
                    "' . addslashes($user['Organismo']) . '",
                    "' . addslashes($user['Unidad']) . '",
                    "' . $user['RutUnidad'] . '",
                    "' . addslashes($user['Direccion']) . '",
                    "' . $user['citName'] . '",
                    "' . $user['disName'] . '");
                ';
                try {
                    $db->query($query);
                } catch (Exception $e) {
                    $this->logger->critical('Save Ctacorriente Customers - QUERY: ' . $query . ' Error message: ' . $e->getMessage());
                }
            }

        }
        $this->logger->info('Importados ' . $response['Cantidad'] . ' usuarios desde Cuenta Corriente');

        return $this;

    }

    private function getCustomers()
    {
        
    }

    private function parseCustomer() {}
}
