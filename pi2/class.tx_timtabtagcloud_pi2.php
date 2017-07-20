<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006 Frank Nägler <typo3@naegler.net>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

require_once(PATH_tslib.'class.tslib_pibase.php');


/**
 * Plugin 'Tag-Cloud' for the 'timtab_tagcloud' extension.
 *
 * @author	Frank Nägler <typo3@naegler.net>
 * @package	TYPO3
 * @subpackage	tx_timtabtagcloud
 */
class tx_timtabtagcloud_pi2 extends tslib_pibase {
	var $prefixId = 'tx_timtabtagcloud_pi2';		// Same as class name
	var $scriptRelPath = 'pi2/class.tx_timtabtagcloud_pi2.php';	// Path to this script relative to the extension dir.
	var $extKey = 'timtab_tagcloud';	// The extension key.
	var $pi_checkCHash = FALSE;

	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	string		The content that is displayed on the website
	 */
	function main($content,$conf)	{
	    	$this->conf = $conf;
	    	$this->timtabconf = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_timtab.'];

		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();

		if (!isset($this->conf['pid'])) $this->conf['pid'] = $this->cObj->data['pages'];

		$this->GP = t3lib_div::_GET('tx_timtabtagcloud');
		$ids = explode("|", $this->GP['ids']);
		$tag = $this->GP['tag'];
		$this->tagname = ($tag == null)?($this->GP['tagname']):($tag);
		$this->counter = 0;
		if ($tag == null) {
			$idCount = count($ids);
			for($i=0;$i<$idCount;$i++) {
				if ($ids[$i] != "")
					$where .= 'uid = ' . intval($ids[$i]) . ' OR ';
			}
			$where .= 'uid = 0';

                	$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                        	'uid, title, keywords',
	                        'tt_news',
        	                $where . $this->cObj->enableFields("tt_news")
                	);
		} else {
                	$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                        	'uid, title',
	                        'tt_news',
        	                'keywords like \'%'.$GLOBALS['TYPO3_DB']->quoteStr($tag, 'tt_news').'%\' AND pid=' . $this->conf['pid'] . ' ' . $this->cObj->enableFields("tt_news")
                	);
		}
		
		$list = '<ul class="tx-timtabtagcloud-postlist">';
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$list .= '<li>' . $this->getPostLink($row['uid'], $row['title']) . '</li>';
			$this->counter++;
		}
		$list .= '</ul>';

		$txtBeforeList = '';
		$txtAfterList = '';

		if ($this->conf['textBeforeList'] != 0) {
			if ($this->counter > 1) {
				$labelToRetrieve = 'text_before_list_plural';
			} else {
				$labelToRetrieve = 'text_before_list_singular';
			}
			$txtBeforeList = $this->prepareText(
				$this->cObj->stdWrap(
					$this->pi_getLL($labelToRetrieve),
					$this->conf['textBeforeList.']
				)
			);
		}

		if ($this->conf['textAfterList'] != 0) {
			if ($this->counter > 1) {
				$labelToRetrieve = 'text_after_list_plural';
			} else {
				$labelToRetrieve = 'text_after_list_singular';
			}
			$txtAfterList = $this->prepareText(
				$this->cObj->stdWrap(
					$this->pi_getLL($labelToRetrieve),
					$this->conf['textAfterList.']
				)
			);
		}

		// combine the texts and the list of posts
 		$content .= $txtBeforeList . $list . $txtAfterList;

		return $this->pi_wrapInBaseClass($content);
	}


   /**
    * generte the link to the blog post entry
    *
    * @param   integer     $id: the id of the tt_news record
    * @param   string      $title: the link title
    * @return  the complete anchor to post
    */
	function getPostLink($id, $title) {
		$urlParams = array(
			'tx_ttnews[tt_news]'  => $id
		);

		$tagAttribs = ' title="'.$title.'"';

		$conf = array(
			'useCacheHash'     => $this->conf['allowCaching'],
			'no_cache'         => !$this->conf['allowCaching'],
			'parameter'        => $this->timtabconf['blogPid'],
			'additionalParams' => $this->conf['parent.']['addParams'].t3lib_div::implodeArrayForUrl('',$urlParams,'',1).$this->pi_moreParams,
			'ATagParams'       => $tagAttribs
		);

		return $this->cObj->typoLink($title, $conf);
	}

   /**
    * prepare the text which is displayed before or after the list of posts
    *
    * @param   string      $txt: the text with placeholder
    * @return  the prepared string with replaces placeholder
    */
	function prepareText($txt) {
		$txt = str_replace("%tagcount%", $this->counter, $txt);
		$txt = str_replace("%tag%", $this->tagname, $txt);
		return $txt;
	}
}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/timtab_tagcloud/pi2/class.tx_timtabtagcloud_pi2.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/timtab_tagcloud/pi2/class.tx_timtabtagcloud_pi2.php']);
}

?>
