<?php


namespace Customcode\RedirectToHome\Plugin;

class RedirectCustomUrl
{

    public function afterExecute(
        \Magento\Customer\Controller\Account\LoginPost $subject,
        $result)
    {
        $customUrl = '/';
        $result->setPath($customUrl);
        return $result;
    }

}
