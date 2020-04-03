<?php
/**
 * The MIT License (MIT)
 *
 * Copyright (c) 2019 Pulchritudinous
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
?>
<?php
/**
 * Collection iterator.
 *
 * @author Anton Samuelsson <samuelsson.anton@gmail.com>
 */
class Pulchritudinous_Queue_Model_Iterator
    implements Iterator
{
    /**
     * Number of rows collected each time.
     *
     * @var integer
     */
    const PAGE_SIZE = 50;

    /**
     * Entity collection.
     *
     * @var Varien_Data_Collection
     */
    protected $_collection;

    /**
     * Current item.
     *
     * @var Varien_Object|null
     */
    protected $_data;

    /**
     * Current slice.
     *
     * @var array
     */
    protected $_slice = [];

    /**
     * Is initialized.
     *
     * @var boolean
     */
    protected $_initiated = false;

    /**
     * Is valid.
     *
     * @var boolean
     */
    protected $_isValid = false;

    /**
     * Current slice index.
     *
     * @var integer
     */
    protected $_index = 0;

    /**
     * Current page.
     *
     * @var integer
     */
    protected $_page;

    /**
     * Initial page.
     *
     * @var integer
     */
    protected $_initPage;

    /**
     * Last page.
     *
     * @var integer
     */
    protected $_lastPage;

    /**
     * Initialize collection iterator.
     *
     * @param  Varien_Data_Collection $collection
     *
     * @throws Mage_Core_Exception
     */
    public function __construct(Varien_Data_Collection $collection)
    {
        $collection->setPageSize(self::PAGE_SIZE);

        $this->_initPage    = $collection->getCurPage();
        $this->_page        = $collection->getCurPage();
        $this->_lastPage    = $collection->getLastPageNumber();

        $this->_collection = $collection;
    }

    /**
     * Load more data into slice.
     *
     * @return Pulchritudinous_Queue_Model_Iterator
     */
    protected function _loadMore()
    {
        $this->_index = 0;
        $this->_slice = [];

        if ($this->_page > $this->_lastPage) {
            return $this;
        }

        $collection = $this->_collection
            ->setCurPage(++$this->_page)
            ->load();

        foreach ($collection as $item) {
            $this->_slice[] = $item;
        }

        $this->_lastPage = $this->_collection
             ->getLastPageNumber();

        $this->_collection->clear();

        return $this;
    }

    /**
     * Reset collection to initial state.
     */
    public function rewind()
    {
        $this->next();
        $this->_collection->setCurPage($this->_initPage);
    }

    /**
     * Return current item.
     *
     * @return Varien_Object|null
     */
    public function current()
    {
        return ($this->valid()) ? $this->_data : null;
    }

    /**
     * This method returns the current entity id.
     *
     * @return integer
     */
    public function key()
    {
        return ($this->valid()) ? $this->_data->getId() : null;
    }

    /**
     * Get next entity.
     *
     * @return boolean
     */
    public function next()
    {
        $this->_data = next($this->_slice);

        if (false === $this->_data) {
            $this->_loadMore();
            $this->_data = next($this->_slice);
        }

        $this->_isValid = (false !== $this->_data);

        return $this->_data;
    }

    /**
     * This method checks if the next row is a valid row.
     *
     * @return boolean
     */
    public function valid()
    {
        return $this->_isValid;
    }
}

