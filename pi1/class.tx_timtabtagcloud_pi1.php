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
class tx_timtabtagcloud_pi1 extends tslib_pibase {
	var $prefixId = 'tx_timtabtagcloud_pi1';		// Same as class name
	var $scriptRelPath = 'pi1/class.tx_timtabtagcloud_pi1.php';	// Path to this script relative to the extension dir.
	var $extKey = 'timtab_tagcloud';	// The extension key.
	var $pi_checkCHash = TRUE;
	
	/**
	 * The main method of the PlugIn
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	The content that is displayed on the website
	 */
	function main($content,$conf)	{
	    	$this->conf = $conf;
	    	$this->timtabconf = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_timtab.'];

		$this->pi_setPiVarDefaults();
		$this->pi_loadLL();
		
		if (!isset($this->conf['pid'])) $this->conf['pid'] = $this->cObj->data['pages'];
		if (empty($this->conf['pid'])) $this->conf['pid'] = $this->cObj->data['pages'];
		
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'uid, keywords',
			'tt_news',
			'pid=' . $this->conf['pid'] . $this->cObj->enableFields("tt_news")
		);
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$keywords = explode($this->conf['delimiter'], $row['keywords']);
			for ($i=0;$i<count($keywords);$i++) {
				$tag = trim($keywords[$i]);
				if (($tag != "") && ($tag != " ")) {
					$tags[$tag]['tag'] = $tag;
					$tags[$tag]['count']++;
					$tags[$tag]['ids'] .= $row['uid'] . "|";
				}
			}
		}
		
		$tags = $this->sortTagArray($tags, $this->conf['sorting']);

		$max = null;
		$min = null;
		$sum = 0;
		$factor = 0;
		$counter = 0;
		$gradation = $this->conf['gradation'];
		foreach ($tags as $key => $value) {
			if (($key != "") && ($value['count'] >= $this->conf['mincount'])) {
				$sum = $sum + $value['count'];
				if ($max == null) $max = $value['count'];
				if ($min == null) $min = $value['count'];
				if ($max < $value['count']) $max = $value['count'];
				if ($min > $value['count']) $min = $value['count'];
				$counter++;
			}
			if (($this->conf['limit'] <> -1) && ($counter == $this->conf['limit'])) break;
		}
		
		$diff = $max - $min;
		$delta = $diff / $gradation;
		for ($i = 1; $i <=$gradation; $i++) {
			$classes[$i] = round($min + $i * $delta);
		}
		
		if (($this->conf['shuffle']) && $this->conf['limit'] <> -1) {
			$l = $this->conf['limit'];
			$c = 0;
			foreach ($tags as $key => $value) {
				if ($value['count'] >= $this->conf['mincount']) {
					$keys[$c] = $key;
					$c++;
				}
				if ($c == $l) break;
			}
			shuffle($keys);
			for ($i=0;$i<count($keys);$i++) {
				$class = 1;
				for ($j=1;$j<=$gradation;$j++) {
					if ($tags[$keys[$i]]['count'] > $classes[$j]) $class = $j;
					continue;
				}
				$content .= $this->getTagLink($tags[$keys[$i]], $class) . " ";
			}
		} else {
		
			$counter = 0;
			foreach ($tags as $key => $value) {
				if ($value['count'] >= $this->conf['mincount']) {
					$class = 1;
					for ($i=1;$i<=$gradation;$i++) {
						if ($value['count'] > $classes[$i]) $class = $i;
						continue;
					}
					$content .= $this->getTagLink($value, $class) . " ";
					$counter++;
				}
				if (($this->conf['limit'] <> -1) && ($counter == $this->conf['limit'])) break;
			}
		}

		return $this->pi_wrapInBaseClass($content);
	}

   /**
    * sort the array of tags
    *
    * @param   array      $data: the tag array
    * @param   string     $direction: sort direction: asc or desc
    * @return  array      the sorted array of tags
    */
	function sortTagArray($data, $direction) {
		if (($direction != "asc") && ($direction != "desc")) return $data;

		usort($data, array($this, "compareTagsByCount"));

		switch ($direction) {
			case "asc"	: return $data; break;
			case "desc"	: return array_reverse($data); break;
		}
	}
	
   /**
    * compare function for the sortTagArray-Function
    *
    * @param   array      $current: current tag entry
    * @param   array      $next: ..next tag entry
    * @return  integer    the result of the comparison 0, 1 or -1
    */
	function compareTagsByCount($current, $next) {
		if ($current['count'] == $next['count']) {
			return 0;
		}
		return ($current['count'] < $next['count']) ? -1 : 1;
	}

   /**
    * generate the link for tagcloud entry
    *
    * @param   array      $data: array with tag data
    * @param   string     $direction: the css class for the size
    * @return  string     return the complete anchor: <a href... 
    */
	function getTagLink($data, $class) {
		if ($this->conf['linkType'] == "ids") {
			$urlParams = array(
				'tx_timtabtagcloud[ids]'  => $data['ids'],
				'tx_timtabtagcloud[tagname]'  => $data['tag']
			);
		} else if ($this->conf['linkType'] == "tag") {
			$urlParams = array(
				'tx_timtabtagcloud[tag]'  => $data['tag']
			);
		}

		$tagAttribs  = ' title="'.$data['tag'].' ('.$data['count'].')"';
		$tagAttribs .= ' class="tx-timtabtagcloud-link"';

		$conf = array(
			'parameter'        => $this->conf['listPid'],
			'no_cache'         => 1,
			'additionalParams' => $this->conf['parent.']['addParams'].t3lib_div::implodeArrayForUrl('',$urlParams,'',1).$this->pi_moreParams,
			'ATagParams'       => $tagAttribs
		);

		return $this->cObj->typoLink('<span class="tx-timtabtagcloud-tag'.$class.'">'.$data['tag'].'</span>', $conf);
	}

}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/timtab_tagcloud/pi1/class.tx_timtabtagcloud_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/timtab_tagcloud/pi1/class.tx_timtabtagcloud_pi1.php']);
}

?>
