<?php

/*  Copyright 2008  Qian Qin  (email : mail@qianqin.de)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/* mqTranslate Utilitys */

function qtrans_parseURL($url) {
	$r  = '!(?:(\w+)://)?(?:(\w+)\:(\w+)@)?([^/:]+)?';
	$r .= '(?:\:(\d*))?([^#?]+)?(?:\?([^#]+))?(?:#(.+$))?!i';

	preg_match ( $r, $url, $out );
	$result = @array(
		"scheme" => $out[1],
		"host" => $out[4].(($out[5]=='')?'':':'.$out[5]),
		"user" => $out[2],
		"pass" => $out[3],
		"path" => $out[6],
		"query" => $out[7],
		"fragment" => $out[8]
		);
	return $result;
}

function qtrans_stripSlashesIfNecessary($str) {
	return get_magic_quotes_gpc() ? stripslashes($str) : $str;
	}

function qtrans_insertDropDownElement($language, $url, $id){
	global $q_config;
	$html ="
	var sb = document.getElementById('qtrans_select_".$id."');
	var o = document.createElement('option');
	var l = document.createTextNode('".$q_config['language_name'][$language]."');
	";
	if($q_config['language']==$language)
		$html .= "o.selected = 'selected';";
	$html .= "
	o.value = '".addslashes(htmlspecialchars_decode($url, ENT_NOQUOTES))."';
	o.appendChild(l);
	sb.appendChild(o);
	";
	return $html;
}

function qtrans_getLanguage() {
	global $q_config;
	return $q_config['language'];
}

function qtrans_getLanguageName($lang = '') {
	global $q_config;
	if($lang=='' || !qtrans_isEnabled($lang)) $lang = $q_config['language'];
	return $q_config['language_name'][$lang];
}

function qtrans_isEnabled($lang) {
	global $q_config;
	return in_array($lang, $q_config['enabled_languages']);
}

function qtrans_startsWith($s, $n) {
	if(strlen($n)>strlen($s)) return false;
	if($n == substr($s,0,strlen($n))) return true;
	return false;
}

function qtrans_getAvailableLanguages($text) {
	global $q_config;
	$result = array();
	$content = qtrans_split($text);
	foreach($content as $language => $lang_text) {
		$lang_text = trim($lang_text);
		if(!empty($lang_text)) $result[] = $language;
	}
	if(sizeof($result)==0) {
		// add default language to keep default URL
		$result[] = $q_config['language'];
	}
	return $result;
}

function qtrans_isAvailableIn($post_id, $language='') {
	global $q_config;
	if($language == '') $language = $q_config['default_language'];
	$post = &get_post($post_id);
	$languages = qtrans_getAvailableLanguages($post->post_content);
	return in_array($language,$languages);
}

function qtrans_convertDateFormatToStrftimeFormat($format) {
	$mappings = array(
		'd' => '%d',
		'D' => '%a',
		'j' => '%E',
		'l' => '%A',
		'N' => '%u',
		'S' => '%q',
		'w' => '%f',
		'z' => '%F',
		'W' => '%V',
		'F' => '%B',
		'm' => '%m',
		'M' => '%b',
		'n' => '%i',
		't' => '%J',
		'L' => '%k',
		'o' => '%G',
		'Y' => '%Y',
		'y' => '%y',
		'a' => '%P',
		'A' => '%p',
		'B' => '%K',
		'g' => '%l',
		'G' => '%L',
		'h' => '%I',
		'H' => '%H',
		'i' => '%M',
		's' => '%S',
		'u' => '%N',
		'e' => '%Q',
		'I' => '%o',
		'O' => '%O',
		'U' => '%s',
		'T' => '%v',
		'Z' => '%1',
		'c' => '%2',
		'r' => '%3',
		'P' => '%4'
		);

	$date_parameters = array();
	$strftime_parameters = array();
	$date_parameters[] = '#%#'; 			$strftime_parameters[] = '%%';
	foreach($mappings as $df => $sf) {
		$date_parameters[] = '#(([^%\\\\])'.$df.'|^'.$df.')#';	$strftime_parameters[] = '${2}'.$sf;
	}
	// convert everything
	$format = preg_replace($date_parameters, $strftime_parameters, $format);
	// remove single backslashes from dates
	$format = preg_replace('#\\\\([^\\\\]{1})#','${1}',$format);
	// remove double backslashes from dates
	$format = preg_replace('#\\\\\\\\#','\\\\',$format);
	return $format;
}

function qtrans_convertFormat($format, $default_format) {
	global $q_config;
	// check for multilang formats
	$format = qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage($format);
	$default_format = qtrans_useCurrentLanguageIfNotFoundUseDefaultLanguage($default_format);
	switch($q_config['use_strftime']) {
		case QT_DATE:
		if($format=='') $format = $default_format;
		return qtrans_convertDateFormatToStrftimeFormat($format);
		case QT_DATE_OVERRIDE:
		return qtrans_convertDateFormatToStrftimeFormat($default_format);
		case QT_STRFTIME:
		return $format;
		case QT_STRFTIME_OVERRIDE:
		return $default_format;
	}
}

function qtrans_convertDateFormat($format) {
	global $q_config;
	if(isset($q_config['date_format'][$q_config['language']])) {
		$default_format = $q_config['date_format'][$q_config['language']];
	} elseif(isset($q_config['date_format'][$q_config['default_language']])) {
		$default_format = $q_config['date_format'][$q_config['default_language']];
	} else {
		$default_format = '';
	}
	return qtrans_convertFormat($format, $default_format);
}

function qtrans_convertTimeFormat($format) {
	global $q_config;
	if(isset($q_config['time_format'][$q_config['language']])) {
		$default_format = $q_config['time_format'][$q_config['language']];
	} elseif(isset($q_config['time_format'][$q_config['default_language']])) {
		$default_format = $q_config['time_format'][$q_config['default_language']];
	} else {
		$default_format = '';
	}
	return qtrans_convertFormat($format, $default_format);
}

function qtrans_formatCommentDateTime($format) {
	global $comment;
	return qtrans_strftime(qtrans_convertFormat($format, $format), mysql2date('U',$comment->comment_date), '', $before, $after);
}

function qtrans_formatPostDateTime($format) {
	global $post;
	return qtrans_strftime(qtrans_convertFormat($format, $format), mysql2date('U',$post->post_date), '', $before, $after);
}

function qtrans_formatPostModifiedDateTime($format) {
	global $post;
	return qtrans_strftime(qtrans_convertFormat($format, $format), mysql2date('U',$post->post_modified), '', $before, $after);
}

function qtrans_realURL($url = '') {
	global $q_config;
	return $q_config['url_info']['original_url'];
}

function qtrans_getSortedLanguages($reverse = false) {
	global $q_config;
	$languages = $q_config['enabled_languages'];
	ksort($languages);
	// fix broken order
	$clean_languages = array();
	foreach($languages as $lang) {
		$clean_languages[] = $lang;
	}
	if($reverse) krsort($clean_languages);
	return $clean_languages;
}
function qtrans_getPicklistOptions() {
	$args = array( 'public' => true, 'exclude_from_search' => false, '_builtin' => false );
	$output = 'objects';
	$operator = 'or';
	$postTypes = get_post_types( $args, $output, $operator ) ;
	foreach ($postTypes as $key => $type) {
		$options[$key] = $type->label;
	}
	return $options;
}
function qtrans_testToDisableTranslation(){
	global $q_config;

	if( in_array(qtrans_getCurrentPostType(), $q_config['ignored_custompost'] ) ) {
		define('QT_DISABLED',true);
		switch (current_filter()) {
			case 'pre_post_update':
			remove_action('post_updated',					'mqtrans_postUpdated',10);
			break;
			case 'admin_notices':
			default:
			remove_filter('widgets_init',					'qtrans_widget_init');
			remove_filter('plugins_loaded',					'qtrans_init', 2);
			remove_filter('init', 							'qtrans_postInit');
			remove_filter('admin_head',						'qtrans_adminHeader');
			remove_filter('admin_menu',						'qtrans_adminMenu');
			remove_filter('wp_before_admin_bar_render',		'qtrans_fixAdminBar');
			remove_filter('wp_tiny_mce_init', 				'qtrans_TinyMCE_init');
			remove_filter('admin_footer', 					'qtrans_modifyExcerpt');
			remove_filter('the_editor', 					'qtrans_modifyRichEditor');
			break;
		}
		return false;
	}
	/**/
	return true;
}
function qtrans_admin_notice_disabled_bypost() {
	if(defined('QT_DISABLED')):
		?><div class="update-nag">
	<p><?php _e( 'Translation disabled on this post type', 'mqtranslate' ); ?></p>
</div>
<?php
endif;
}

function qtrans_getCurrentPostType() {
	global $post, $typenow, $current_screen, $post_type;

	if( $post_type ) return $post_type;

	$post_id = ( isset($_GET['post']) ) ? $_GET['post'] : ( isset($_POST['post_ID']) ) ? $_POST['post_ID'] : 0;

	$post = NULL;
	$post_type_object = NULL;
	if ( $post_id && $post = get_post($post_id) ) {
		if ( $post_type_object = get_post_type_object($post->post_type) ) {
			return $post_type_object->name;
		}
	} elseif ( isset($_POST['post_type']) && $post_type_object = get_post_type_object($_POST['post_type']) ) {
		return $post_type_object->name;
	}  elseif( $typenow ){
		return $typenow;
	} elseif( $current_screen && $current_screen->post_type ){
		return  $current_screen->post_type;
	} elseif( isset( $_REQUEST['post_type'] ) ){
		return  sanitize_key( $_REQUEST['post_type'] );
	}elseif (get_post_type($_REQUEST['post'])){
		return get_post_type($_REQUEST['post']);
	}
	if( $post_type = apply_filters( 'qtrans_testCustomPostType', "" ) ) return $post_type;

	//last chance to get the post_type (this can be wrong)
	$regex = "/^.*(id)$/i";
	$vars = array();
	foreach($_GET as $name=>$value) {
		if(preg_match($regex, $name)) {
			$vars[$name] = $value;
			if ( $post = get_post($value) ) {
				if ( $post_type_object = get_post_type_object($post->post_type) ) {
					return  $post_type_object->name;
				}
			}
		}
	}
	return "undefined_posttype";
}
function qtrans_if($a,$b, $post_type){
	global $current_screen;
	return ($current_screen->base == $test_screen_base || $_POST['action'] == $test_screen_base) ? $post_type : false;
}

?>