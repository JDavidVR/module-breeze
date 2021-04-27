<?php

namespace Swissup\Breeze\Helper;

use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    /**
     * @var boolean
     */
    private $isEnabled = null;

    /**
     * @param Context $context
     * @param \Magento\Framework\View\ConfigInterface $viewConfig
     */
    public function __construct(
        Context $context,
        \Magento\Framework\View\ConfigInterface $viewConfig
    ) {
        parent::__construct($context);

        $this->viewConfig = $viewConfig;
    }

    /**
     * @return boolean
     */
    public function isEnabled()
    {
        if ($this->isEnabled !== null) {
            return $this->isEnabled;
        }

        if ($this->_getRequest()->getParam('amp')) {
            $this->isEnabled = false;
        } else {
            $this->isEnabled = $this->getConfig('design/breeze/enabled');

            if ($this->isEnabled === 'theme') {
                $this->isEnabled = $this->getThemeConfig('enabled');
            }

            if ($this->getConfig('design/breeze/debug')) {
                $flag = $this->_getRequest()->getParam('breeze');
                if ($flag !== null) {
                    $this->isEnabled = $flag;
                }
            }

            $this->isEnabled = (bool) $this->isEnabled;
        }

        if ($this->isEnabled) {
            $this->isEnabled = $this->isCurrentPageSupported();
        }

        return $this->isEnabled;
    }

    protected function isCurrentPageSupported()
    {
        $page = $this->_request->getFullActionName();

        return strpos($page, 'checkout_') === false
            && strpos($page, 'multishipping_') === false;
    }

    /**
     * @return boolean
     */
    public function isTurboEnabled()
    {
        return false;

        $result = $this->getConfig('design/breeze/turbo');

        if ($result === 'theme') {
            $result = $this->getThemeConfig('turbo');
        }

        return (bool) $result;
    }

    /**
     * @param string $key
     * @return string
     */
    public function getThemeConfig($key)
    {
        return $this->viewConfig
            ->getViewConfig()
            ->getVarValue('Swissup_Breeze', $key);
    }

    /**
     * @param  string $path
     * @param  string $scope
     * @return string
     */
    public function getConfig($path, $scope = ScopeInterface::SCOPE_STORE)
    {
        return $this->scopeConfig->getValue($path, $scope);
    }
}
