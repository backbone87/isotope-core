<?php if (!defined('TL_ROOT')) die('You can not access this file directly!');

/**
 * TYPOlight Open Source CMS
 * Copyright (C) 2005-2010 Leo Feyer
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at <http://www.gnu.org/licenses/>.
 *
 * PHP version 5
 * @copyright  Winans Creative 2009, Intelligent Spark 2010, iserv.ch GmbH 2010
 * @author     Fred Bliss <fred.bliss@intelligentspark.com>
 * @author     Andreas Schempp <andreas@schempp.ch>
 * @license    http://opensource.org/licenses/lgpl-3.0.html
 */
 

/**
 * Parent class for all shipping gateway modules
 */
abstract class IsotopeShipping extends Frontend
{

	/**
	 * Template
	 *
	 * @access protected
	 * @var string
	 */
	protected $strTemplate;

	/**
	 * Current record
	 *
	 * @access protected
	 * @var array
	 */
	protected $arrData = array();
	
	
	protected $Isotope;
	protected $Cart;
	
	
	/**
	 * Initialize the object
	 *
	 * @access public
	 * @param array $arrRow
	 */
	public function __construct($arrRow)
	{
		parent::__construct();
		
		$this->import('Isotope');
		
		if(TL_MODE=='FE')
			$this->import('IsotopeCart', 'Cart');
		
		$this->arrData = $arrRow;
	}
	
	
	/**
	 * Set an object property
	 *
	 * @access public
	 * @param string
	 * @param mixed
	 */
	public function __set($strKey, $varValue)
	{
		$this->arrData[$strKey] = $varValue;
	}


	/**
	 * Return an object property
	 *
	 * @access public
	 * @param string
	 * @return mixed
	 */
	public function __get($strKey)
	{		
		
		switch( $strKey )
		{
			case 'available':
				if (($this->guests && FE_USER_LOGGED_IN) || ($this->protected && !FE_USER_LOGGED_IN))
				{			
					return false;
				}
				
				if ($this->protected)
				{					
					$this->import('FrontendUser', 'User');
					$arrGroups = deserialize($this->groups);
					if (!is_array($arrGroups) || !count($arrGroups) || !count(array_intersect($arrGroups, $this->User->groups)))
						return false;
				}
				
				if (($this->minimum_total > 0 && $this->minimum_total > $this->Cart->subTotal) || ($this->maximum_total > 0 && $this->maximum_total < $this->Cart->subTotal))				
					return false;
				
				$arrCountries = deserialize($this->countries);
			
				if(is_array($arrCountries) && count($arrCountries) && !in_array($this->Cart->shippingAddress['country'], $arrCountries))
					return false;
					
				$arrSubdivisions = deserialize($this->subdivisions);
				$blnHasSubdivision = is_array($GLOBALS['TL_LANG']['DIV'][$this->Cart->shippingAddress['country']]);	//!@todo this should be dropped with Contao 2.9 as all countries "should" have subdivisions
				
				if(is_array($arrSubdivisions) && count($arrSubdivisions) && !in_array($this->Cart->shippingAddress['subdivision'], $arrSubdivisions) && $blnHasSubdivision)
					return false;
								
				return true;
				break;
				
			case 'price':
				return $this->Isotope->calculatePrice($this->arrData['price'], $this->arrData['tax_class']);
				break;
		}
		
		return $this->arrData[$strKey];
	}
	
	
	/**
	 * Return a list of buttons for the table row in backend
	 * 
	 * @access public
	 * @return string
	 */
	public function moduleOperations()
	{
		return '';
	}
	
	
	/**
	 * Process post-sale requests. Does nothing by default.
	 *
	 * This function can be called from the postsale.php file when the shipping server is requestion/posting a status change.
	 * You can see an implementation example in PaymentPostfinance.php
	 * 
	 * @abstract
	 * @access public
	 * @return void
	 */
	public function processPostSale() {}
	

	/**
	 * This function is used to gather any addition shipping options that might be available specific to the current customer or order.  For example, expedited shipping based on 		 * customer location.
	 * 
	 * @abstract
	 * @access public
	 * @return void
	 */
	public function getShippingOptions() {}
	
	
	/**
	 * Return the checkout review information.
	 *
	 * Use this to return custom checkout information about this shipping module.
	 * Example: Information about tracking codes.
	 *
	 * @access public
	 * @return string
	 */
	public function checkoutReview()
	{
		return $this->label;
	}
}

