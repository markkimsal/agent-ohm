<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category   Mage
 * @package    Mage_CatalogSearch
 * @copyright  Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Catalog search helper
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Mage_CatalogSearch_Helper_Data extends Mage_Core_Helper_Abstract
{
    const QUERY_VAR_NAME = 'q';
    const MAX_QUERY_LEN  = 200;

    /**
     * Query object
     *
     * @var Mage_CatalogSearch_Model_Query
     */
    protected $_query;

    /**
     * Query string
     *
     * @var string
     */
    protected $_queryText;

    /**
     * Note messages
     *
     * @var array
     */
    protected $_messages = array();

    /**
     * Is a maximum length cut
     *
     * @var bool
     */
    protected $_isMaxLength = false;

    /**
     * Retrieve search query parameter name
     *
     * @return string
     */
    public function getQueryParamName()
    {
        return self::QUERY_VAR_NAME;
    }

    /**
     * Retrieve query model object
     *
     * @return Mage_CatalogSearch_Model_Query
     */
    public function getQuery()
    {
        if (!$this->_query) {
            $this->_query = Mage::getModel('catalogsearch/query')
                ->loadByQuery($this->getQueryText())
                ->setQueryText($this->getQueryText());
        }
        return $this->_query;
    }

    /**
     * Is a minimum query length
     *
     * @return bool
     */
    public function isMinQueryLength()
    {
        if (Mage::helper('core/string')->strlen($this->getQueryText()) < $this->getMinQueryLength()) {
            return true;
        }
        return false;
    }

    /**
     * Retrieve search query text
     *
     * @return string
     */
    public function getQueryText()
    {
        if (is_null($this->_queryText)) {
            $this->_queryText = $this->_getRequest()->getParam($this->getQueryParamName());
            if ($this->_queryText === null) {
                $this->_queryText = '';
            } else {
                if (is_array($this->_queryText)) {
                    $this->_queryText = null;
                }
                $this->_queryText = trim($this->_queryText);
                if (Mage::helper('core/string')->strlen($this->_queryText) > $this->getMaxQueryLength()) {
                    $this->_queryText = Mage::helper('core/string')->substr(
                        $this->_queryText,
                        0,
                        $this->getMaxQueryLength()
                    );
                    $this->_isMaxLength = true;
                }
            }
        }
        return $this->_queryText;
    }

    /**
     * Retrieve HTML escaped search query
     *
     * @return string
     */
    public function getEscapedQueryText()
    {
        return $this->htmlEscape($this->getQueryText());
    }

    /**
     * Retrieve suggest collection for query
     *
     * @return Mage_CatalogSearch_Model_Mysql4_Query_Collection
     */
    public function getSuggestCollection()
    {
        return $this->getQuery()->getSuggestCollection();
    }

    /**
     * Retrieve result page url
     *
     * @param   string $query
     * @return  string
     */
    public function getResultUrl($query = null)
    {
        return $this->_getUrl('catalogsearch/result', array('_query' => array(
            self::QUERY_VAR_NAME => $query
        )));
    }

    /**
     * Retrieve suggest url
     *
     * @return string
     */
    public function getSuggestUrl()
    {
        return $this->_getUrl('catalogsearch/ajax/suggest');
    }

    /**
     * Retrieve search term url
     *
     * @return string
     */
    public function getSearchTermUrl()
    {
        return $this->_getUrl('catalogsearch/term/popular');
    }

    /**
     * Retrieve advanced search URL
     *
     * @return string
     */
    public function getAdvancedSearchUrl()
    {
        return $this->_getUrl('catalogsearch/advanced');
    }

    /**
     * Retrieve minimum query length
     *
     * @param mixed $store
     * @return int
     */
    public function getMinQueryLength($store = null)
    {
        return Mage::getStoreConfig(Mage_CatalogSearch_Model_Query::XML_PATH_MIN_QUERY_LENGTH, $store);
    }

    /**
     * Retrieve maximum query length
     *
     * @param mixed $store
     * @return int
     */
    public function getMaxQueryLength($store = null)
    {
        return Mage::getStoreConfig(Mage_CatalogSearch_Model_Query::XML_PATH_MAX_QUERY_LENGTH, $store);
    }

    /**
     * Retrieve maximum query words count for like search
     *
     * @param mixed $store
     * @return int
     */
    public function getMaxQueryWords($store = null)
    {
        return Mage::getStoreConfig(Mage_CatalogSearch_Model_Query::XML_PATH_MAX_QUERY_WORDS, $store);
    }

    /**
     * Add Note message
     *
     * @param string $message
     * @return Mage_CatalogSearch_Helper_Data
     */
    public function addNoteMessage($message)
    {
        $this->_messages[] = $message;
        return $this;
    }

    /**
     * Set Note messages
     *
     * @param array $messages
     * @return Mage_CatalogSearch_Helper_Data
     */
    public function setNoteMessages(array $messages)
    {
        $this->_messages = $messages;
        return $this;
    }

    /**
     * Retrieve Current Note messages
     *
     * @return array
     */
    public function getNoteMessages()
    {
        return $this->_messages;
    }

    /**
     * Check query of a warnings
     *
     * @param mixed $store
     * @return Mage_CatalogSearch_Helper_Data
     */
    public function checkNotes($store = null)
    {
        if ($this->_isMaxLength) {
            $this->addNoteMessage($this->__('Maximum Search query  length is %s. Your query was cut.', $this->getMaxQueryLength()));
        }

        $stringHelper = Mage::helper('core/string');
        /* @var $stringHelper Mage_Core_Helper_String */

        $searchType = Mage::getStoreConfig(Mage_CatalogSearch_Model_Fulltext::XML_PATH_CATALOG_SEARCH_TYPE);
        if ($searchType == Mage_CatalogSearch_Model_Fulltext::SEARCH_TYPE_COMBINE ||
            $searchType == Mage_CatalogSearch_Model_Fulltext::SEARCH_TYPE_LIKE) {

            $wordsFull = $stringHelper->splitWords($this->getQueryText(), true);
            $wordsLike = $stringHelper->splitWords($this->getQueryText(), true, $this->getMaxQueryWords());

            if (count($wordsFull) > count($wordsLike)) {
                $wordsCut = array_diff($wordsFull, $wordsLike);

                $this->addNoteMessage(
                    $this->__('Maximum words count is %1$s. In your search query was cut next part: %2$s.',
                        $this->getMaxQueryWords(),
                        join(' ', $wordsCut)
                    )
                );
            }
        }
    }
}
