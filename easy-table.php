<?php
/*
Plugin Name: Easy Table
Plugin URI: http://takien.com/
Description: Create table in post, page, or widget in easy way.
Author: Takien
Version: 0.6.1
Author URI: http://takien.com/
*/

/*  Copyright 2012 takien.com

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    For a copy of the GNU General Public License, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

if(!defined('ABSPATH')) die();

if (!class_exists('EasyTable')) {
class EasyTable {


/**
* Default settings
* Plugin will use this setting if user not made custom setting via settings page or tag.
*/
var $settings 	= Array(
	'shortcodetag'	=> 'table',
	'attrtag'		=> 'attr',
	'tablewidget'	=> false,
	'scriptloadin'	=> Array('is_single','is_page'),
	'class'			=> 'table-striped',
	'caption'		=> false,
	'width'			=> '100%',
	'align'			=> 'left',
	'th'			=> true,
	'tf'			=> false,
	'border'		=> 0,
	'id'			=> false,
	'theme'			=> 'default',
	'tablesorter' 	=> false,
	'loadcss' 		=> true,
	'delimiter'		=> ',',
	'file'			=> false,
	'enclosure' 	=> '&quot;',
	'escape' 		=> '\\',
	'csvfile'		=> false
);


function EasyTable(){
	$this->__construct();
}

function __construct(){
	$plugin = plugin_basename(__FILE__);
	add_filter("plugin_action_links_$plugin",  array(&$this,'easy_table_settings_link' ));
	
	load_plugin_textdomain('easy-table', false, basename( dirname( __FILE__ ) ) . '/languages' );
	
	add_action('admin_init', 		 array(&$this,'easy_table_register_setting'));
	add_action('admin_head',		 array(&$this,'easy_table_admin_script'));
	add_action('wp_enqueue_scripts', array(&$this,'easy_table_script'));
	add_action('wp_enqueue_scripts', array(&$this,'easy_table_style'));
	add_action('admin_menu', 		 array(&$this,'easy_table_add_page'));
	add_action('contextual_help', 	 array(&$this,'easy_table_help'));
	add_shortcode($this->get_easy_table_option('shortcodetag'),  array(&$this,'easy_table_short_code'));
	add_shortcode($this->get_easy_table_option('attrtag'),  array(&$this,'easy_table_short_code_attr'));
	if($this->get_easy_table_option('tablewidget')){
		add_filter('widget_text', 		'do_shortcode');
	}
}


private function easy_table_base($return){
	$easy_table_base = Array(
				'name' 			=> 'Easy Table',
				'version' 		=> '0.6.1',
				'plugin-domain'	=> 'easy-table'
	);
	return $easy_table_base[$return];
}

function easy_table_short_code($atts, $content="") {
	$shortcode_atts = shortcode_atts(array(
		'class' 		=> $this->get_easy_table_option('class'),
		'caption' 		=> $this->get_easy_table_option('caption'),
		'width' 		=> $this->get_easy_table_option('width'),
		'align' 		=> $this->get_easy_table_option('align'),
		'th'	  		=> $this->get_easy_table_option('th'),
		'tf'	  		=> $this->get_easy_table_option('tf'),
		'border'		=> $this->get_easy_table_option('border'),
		'id'	  		=> $this->get_easy_table_option('id'),
		'theme'			=> $this->get_easy_table_option('theme'),
		'tablesorter'	=> $this->get_easy_table_option('tablesorter'),
		'delimiter'		=> $this->get_easy_table_option('delimiter'),
		'enclosure' 	=> $this->get_easy_table_option('enclosure'),
		'escape' 		=> $this->get_easy_table_option('escape'),
		'file'			=> $this->get_easy_table_option('file')
	 ), $atts);

	$content 		= clean_pre($content);
	$content 		= str_replace('&nbsp;','',$content);
	$char_codes 	= array( '&#8216;', '&#8217;', '&#8220;', '&#8221;', '&#8242;', '&#8243;' );
	$replacements 	= array( "'", "'", '"', '"', "'", '"' );
	$content = str_replace( $char_codes, $replacements, $content );
		
	 return $this->csv_to_table($content,$shortcode_atts);
}

/**
* Just return to strip attr shortcode for table cell, since we use custom regex for attr shortcode.
* @since 0.5
*/
function easy_table_short_code_attr($atts){
	return;
}

/**
* Convert CSV to table
* @param array|string $data could be CSV string or array
* @param array @args
* @return string
*/
private function csv_to_table($data,$args){
	extract($args);
	
	if($file){
		$data = @file_get_contents($file);
	}

	if(empty($data)) return false;

	if(!is_array($data)){
		$data 	= $this->csv_to_array(trim($data), $delimiter, html_entity_decode($enclosure), $escape);
	}
	$max_cols 	= count(max($data));
	$i=0;
	/**
	* tfoot position
	* @since 0.4
	*/
	$tfpos = ($tf == 'last') ? count($data) : ($th?2:1);

	$pos = strpos($width,'px');
	if ($pos === false) {
		$width = (int)$width.'%';
	} else {
		$width = (int)$width.'px';
	}
	$output = '<table '.($id ? 'id="'.$id.'"':'').' style="width:'.$width.';'.(($align=='center') ? 'margin-left:auto;margin-right:auto' : '').'" width="'.(int)$width.'" align="'.$align.'" class="table clearfix '.($tablesorter ? 'tablesorter ':'').$class.'" '.(($border !=='0') ? 'border="'.$border.'"' : '').'>'."\n";
	$output .= $caption ? '<caption>'.$caption.'</caption>'."\n" : '';
	$output .= $th ? '<thead>' : (($tf !== 'last') ? '' : '<tbody>');
	$output .= (!$th AND !$tf) ? '<tbody>':'';
	
	foreach($data as $k=>$cols){ $i++;
		//$cols = array_pad($cols,$max_cols,'');
		
		$output .= (($i==$tfpos) AND $tf) ? (($tf=='last')?'</tbody>':'').'<tfoot>': '';
		$output .= "\r\n".'<tr>';

		$thtd = ((($i==1) AND $th) OR (($i==$tfpos) AND $tf)) ? 'th' : 'td';
		foreach($cols as $col){ 
			/**
			* Add attribute for each cell
			* @since 0.5
			*/
			$attr = preg_match('/\['.$this->get_easy_table_option('attrtag').' ([^\\]\\/]*(?:\\/(?!\\])[^\\]\\/]*)*?)/',$col,$matchattr);
			$attr = isset($matchattr[1]) ? $matchattr[1] : '';
				/**
				* retrieve colspan value, not used at this time
				$colspan = shortcode_parse_atts($attr);
				$colspan = $colspan['colspan']; 
				*/
			$output .= "<$thtd $attr>".do_shortcode($col)."</$thtd>\n";
		}
	
		$output .= '</tr>'."\n";
		$output .= (($i==1) AND $th) ? '</thead>'."\n".'<tbody>' : '';
		$output .= (($i==$tfpos) AND $tf) ? '</tfoot>'.((($tf==1) AND !$th) ? '<tbody>':''): '';
		
	}
	$output .= (($tf!=='last')?'</tbody>':'').'</table>';
	return $output;
}

/**
* Convert CSV to array
*/
private function csv_to_array($csv, $delimiter = ',', $enclosure = '"', $escape = '\\', $terminator = "\n") {
$r = array();

$rows = str_getcsv($csv, $terminator,$enclosure,$escape); 
$rows = array_diff($rows,Array(''));

foreach($rows as &$row) {
	$row = str_getcsv($row, $delimiter,$enclosure,$escape);
	$r[] = $row;
}
return $r;
}

/**
* Retrieve options from database if any, or use default options instead.
*/
function get_easy_table_option($key=''){
	$option = get_option('easy_table_plugin_option') ? get_option('easy_table_plugin_option') : Array();
	$option = array_merge($this->settings,$option);
	if($key){
		$return = $option[$key];
	}
	else{
		$return = $option;
	}
	return $return;

}

/**
* Register plugin setting
*/
function easy_table_register_setting() {
	register_setting('easy_table_option_field', 'easy_table_plugin_option');
}

/**
* Render form
* @param array 
*/	
function render_form($fields){
	$output ='<table class="form-table">';
	foreach($fields as $field){
		if($field['type']=='text'){
			$output .= '<tr><th><label for="'.$field['name'].'">'.$field['label'].'</label></th>';
			$output .= '<td><input type="text" id="'.$field['name'].'" name="'.$field['name'].'" value="'.$field['value'].'" />';
			$output .= ' <span class="description">'.$field['description'].'</span></td></tr>';
		}
		if($field['type']=='checkbox'){
			$output .= '<tr><th><label for="'.$field['name'].'">'.$field['label'].'</label></th>';
			$output .= '<td><input type="hidden" name="'.$field['name'].'" value="" /><input type="checkbox" id="'.$field['name'].'" name="'.$field['name'].'" value="'.$field['value'].'" '.$field['attr'].' />';
			$output .= ' <span class="description">'.$field['description'].'</span></td></tr>';
		}
		if($field['type']=='checkboxgroup'){
			$output .= '<tr><th><label>'.$field['grouplabel'].'</label></th>';
			$output .= '<td>';
			foreach($field['groupitem'] as $key=>$item){
				$output .= '<input type="hidden" name="'.$item['name'].'" value="" /><input type="checkbox" id="'.$item['name'].'" name="'.$item['name'].'" value="'.$item['value'].'" '.$item['attr'].' /> <label for="'.$item['name'].'">'.$item['label'].'</label><br />';
			}
			$output .= ' <span class="description">'.$field['description'].'</span></td></tr>';
		}
	}
	$output .= '</table>';
	return $output;
}

/**
* Register javascript
*/	
function easy_table_script() {
	if(	is_single() AND in_array('is_single',$this->get_easy_table_option('scriptloadin')) OR
		is_page() AND in_array('is_page',$this->get_easy_table_option('scriptloadin')) OR 
		is_home() AND in_array('is_home',$this->get_easy_table_option('scriptloadin')) OR 
		is_archive() AND in_array('is_archive',$this->get_easy_table_option('scriptloadin')))
	{
	if($this->get_easy_table_option('tablesorter')) {
		wp_enqueue_script('jquery');
		wp_register_script('easy_table_script',plugins_url( 'jquery.tablesorter.min.js' , __FILE__ ),'jquery');
		wp_enqueue_script('easy_table_script');
	}
	}
}

/**
* Register stylesheet
*/	
function easy_table_style() {
	if(	is_single() AND in_array('is_single',$this->get_easy_table_option('scriptloadin')) OR
		is_page() AND in_array('is_page',$this->get_easy_table_option('scriptloadin')) OR 
		is_home() AND in_array('is_home',$this->get_easy_table_option('scriptloadin')) OR 
		is_archive() AND in_array('is_archive',$this->get_easy_table_option('scriptloadin')))
	{
	if($this->get_easy_table_option('loadcss')) {
		wp_register_style('easy_table_style', plugins_url('easy-table-style.css', __FILE__),false,$this->easy_table_base('version'));
		//wp_register_style('easy_table_style', plugins_url('/themes/aucity/style.css', __FILE__),false,$this->easy_table_base('version'));
		wp_enqueue_style( 'easy_table_style');
	}
	}
}

function easy_table_admin_script(){
$page = isset($_GET['page']) ? $_GET['page'] : '';
if($page == $this->easy_table_base('plugin-domain')) { 
if($this->get_easy_table_option('tablesorter')) { ?>
<script src="<?php echo plugins_url( 'jquery.tablesorter.min.js' , __FILE__);?>"></script>
<?php }
if($this->get_easy_table_option('loadcss')) { ?>
<link rel="stylesheet" href="<?php echo plugins_url('easy-table-style.css', __FILE__);?> " />
<?php } ?>
<style type="text/css">
	.left,
	.right{
		float:left;
		width:49%
	}
	.toggledesc{
		float:right;
		margin-right:20px;
	}
	.action-button{
		margin-bottom:20px
	}
	.action-button a{
		padding:10px;
		border:1px solid #cccccc;
		-webkit-border-radius: 5px;
		-moz-border-radius: 5px;
		border-radius: 5px; 
		color:#fff;
		font-weight:bold;
		font-size:1.3em;
		display: inline-block;
		text-shadow: 0 -1px 1px rgba(19,65,88,.8);
	}
	.action-button a.green{
		background:#48b826;
		border-color:#1d7003;
		background: #b4e391;
		background: -moz-linear-gradient(top, #b4e391 0%, #61c419 50%, #b4e391 100%);
		background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,#b4e391), color-stop(50%,#61c419), color-stop(100%,#b4e391));
		background: -webkit-linear-gradient(top, #b4e391 0%,#61c419 50%,#b4e391 100%);
		background: -o-linear-gradient(top, #b4e391 0%,#61c419 50%,#b4e391 100%);
		background: -ms-linear-gradient(top, #b4e391 0%,#61c419 50%,#b4e391 100%);
		background: linear-gradient(top, #b4e391 0%,#61c419 50%,#b4e391 100%);
	}
	.action-button a.red{
		background: #f85032;
		background: -moz-linear-gradient(top, #f85032 0%, #f16f5c 35%, #f02f17 71%, #e73827 100%);
		background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,#f85032), color-stop(35%,#f16f5c), color-stop(71%,#f02f17), color-stop(100%,#e73827));
		background: -webkit-linear-gradient(top, #f85032 0%,#f16f5c 35%,#f02f17 71%,#e73827 100%);
		background: -o-linear-gradient(top, #f85032 0%,#f16f5c 35%,#f02f17 71%,#e73827 100%);
		background: -ms-linear-gradient(top, #f85032 0%,#f16f5c 35%,#f02f17 71%,#e73827 100%);
		background: linear-gradient(top, #f85032 0%,#f16f5c 35%,#f02f17 71%,#e73827 100%);
		border-color:#cf3100;
	}
</style>
<script type="text/javascript">
//<![CDATA[
	jQuery(document).ready(function($){
		$('.togglethis a').click(function(e){
			var target = $(this).attr('data-target');
			$(target).toggle();
			e.preventDefault();
		});
	});
//]]>
	</script>
<?php
}
} /* end easy_table_admin_script*/

/**
* Add action link to plugins page
* from plugins listing.
*/
function easy_table_settings_link($links) {
	  $settings_link = '<a href="options-general.php?page='.$this->easy_table_base('plugin-domain').'">'.__('Settings','easy-table').'</a>';
	  array_unshift($links, $settings_link);
	  return $links;
} 

/**
* Contextual help
*/	
function easy_table_help($help) {
	$page = isset($_GET['page']) ? $_GET['page'] : '';
	if($page == $this->easy_table_base('plugin-domain')) {
		$help = '<h2>'.$this->easy_table_base('name').' '.$this->easy_table_base('version').'</h2>';
		$help .= '<h5>'.__('Instruction','easy-table').':</h5>
		<ol><li>'.__('Once plugin installed, go to plugin options page to configure some options','easy-table').'</li>';
		$help .= '<li>'.__('You are ready to write a table in post or page.','easy-table').'</li>';
		$help .= '<li>'.__('To be able write table in widget you have to check <em>Enable render table in widget</em> option in the option page.','easy-table').'</li></ol>';
		
	return $help;
	}
}

/**
* Add plugin page
*/	
function easy_table_add_page() {
	add_options_page($this->easy_table_base('name'), $this->easy_table_base('name'), 'administrator', $this->easy_table_base('plugin-domain'), array(&$this,'easy_table_page'));
}

/**
* Plugin option page
*/	
function easy_table_page() { ?>
<div class="wrap">
<div class="icon32"><img src="<?php echo plugins_url('/images/icon-table.png', __FILE__);?>" /></div>
<h2 class="nav-tab-wrapper">
	<a href="options-general.php?page=<?php echo $this->easy_table_base('plugin-domain');?>" class="nav-tab <?php echo !isset($_GET['gettab']) ? 'nav-tab-active' : '';?>"><?php printf(__('%s Option','easy-table'), $this->easy_table_base('name'));?></a>
	<a href="options-general.php?page=<?php echo $this->easy_table_base('plugin-domain');?>&gettab=support" class="nav-tab <?php echo (isset($_GET['gettab']) AND ($_GET['gettab'] == 'support')) ? 'nav-tab-active' : '';?>"><?php _e('Support','easy-table');?></a>
	<a href="options-general.php?page=<?php echo $this->easy_table_base('plugin-domain');?>&gettab=about" class="nav-tab <?php echo (isset($_GET['gettab']) AND ($_GET['gettab'] == 'about')) ? 'nav-tab-active' : '';?>"><?php _e('About','easy-table');?></a>
</h2>
<?php if(!isset($_GET['gettab'])) : ?>
<div class="left">
<form method="post" action="options.php">
<?php 
wp_nonce_field('update-options'); 
settings_fields('easy_table_option_field');

?>
	<span class="togglethis toggledesc"><em><a href="#" data-target=".description"><?php _e('Show/hide description');?></a></em></span>
	<h3><?php _e('General options','easy-table');?></h3>
	<?php
	$fields = Array(
		Array(
			'name'			=> 'easy_table_plugin_option[shortcodetag]',
			'label'			=> __('Short code tag','easy-table'),
			'type'			=> 'text',
			'description'	=> __('Shortcode tag, type "table" if you want to use [table] short tag.','easy-table'),
			'value'			=> $this->get_easy_table_option('shortcodetag')
			)
		,
		Array(
			'name'			=> 'easy_table_plugin_option[attrtag]',
			'label'			=> __('Cell attribute tag','easy-table'),
			'type'			=> 'text',
			'description'	=> __('Cell attribute tag, default is attr.','easy-table'),
			'value'			=> $this->get_easy_table_option('attrtag')
			)
		,Array(
			'name'			=> 'easy_table_plugin_option[tablewidget]',
			'label'			=> __('Also render table in widget?','easy-table'),
			'type'			=> 'checkbox',
			'description'	=> __('Check this if you want the table could be rendered in widget.','easy-table'),
			'value'			=> 1,
			'attr'			=> $this->get_easy_table_option('tablewidget') ? 'checked="checked"' : '')
		,Array(
			'type'			=> 'checkboxgroup',
			'grouplabel'	=> __('Only load JS/CSS when in this condition','easy-table'),
			'description'	=> __('Please check in where JavaScript and CSS should be loaded','easy-table'),
			'groupitem'		=> Array(
								Array(
								'name' 	=> 'easy_table_plugin_option[scriptloadin][]',
								'label'	=> __('Single','easy-table'),
								'value'	=> 'is_single',
								'attr'	=> in_array('is_single',$this->get_easy_table_option('scriptloadin')) ? 'checked="checked"' : ''
								),
								Array(
								'name' 	=> 'easy_table_plugin_option[scriptloadin][]',
								'label'	=> __('Page','easy-table'),
								'value'	=> 'is_page',
								'attr'	=> in_array('is_page',$this->get_easy_table_option('scriptloadin')) ? 'checked="checked"' : ''
								),
								Array(
								'name' 	=> 'easy_table_plugin_option[scriptloadin][]',
								'label'	=> __('Front page','easy-table'),
								'value'	=> 'is_home',
								'attr'	=> in_array('is_home',$this->get_easy_table_option('scriptloadin')) ? 'checked="checked"' : ''
								),
								Array(
								'name' 	=> 'easy_table_plugin_option[scriptloadin][]',
								'label'	=> __('Archive page','easy-table'),
								'value'	=> 'is_archive',
								'attr'	=> in_array('is_archive',$this->get_easy_table_option('scriptloadin')) ? 'checked="checked"' : ''
								)
								)
		)
	);
	echo $this->render_form($fields);

	$fields = Array(
		Array(	
			'name'			=> 'easy_table_plugin_option[tablesorter]',
			'label'			=> __('Use tablesorter?','easy-table'),
			'type'			=> 'checkbox',
			'value'			=> 1,
			'description'	=> __('Check this to use tablesorter jQuery plugin','easy-table'),
			'attr'			=> $this->get_easy_table_option('tablesorter') ? 'checked="checked"':'')
		,Array(
			'name'			=> 'easy_table_plugin_option[th]',
			'label'			=> __('Use TH for the first row?','easy-table'),
			'type'			=> 'checkbox',
			'value'			=> 1,
			'description'	=> __('Check this if you want to use first row as table head (required by tablesorter)','easy-table'),
			'attr'			=> $this->get_easy_table_option('th') ? 'checked="checked"' : '')
		,Array(
			'name'			=> 'easy_table_plugin_option[loadcss]',
			'label'			=> __('Load CSS?','easy-table'),
			'type'			=> 'checkbox',
			'value'			=> 1,
			'description'	=> __('Check this to use CSS included in this plugin to styling table, you may unceck if you want to write your own style.','easy-table'),
			'attr'			=> $this->get_easy_table_option('loadcss') ? 'checked="checked"':'')	
		,Array(
			'name'			=> 'easy_table_plugin_option[class]',
			'label'			=> __('Table class','easy-table'),
			'type'			=> 'text',
			'description'	=> __('Table class attribute, if you use bootstrap CSS, you should add at least "table" class.','easy-table'),
			'value'			=> $this->get_easy_table_option('class'))
		,Array(
			'name'			=> 'easy_table_plugin_option[width]',
			'label'			=> __('Table width','easy-table'),
			'type'			=> 'text',
			'description'	=> __('Table width, in pixel or percent (may be overriden by CSS)','easy-table'),
			'value'			=> $this->get_easy_table_option('width'))
		,Array(
			'name'			=> 'easy_table_plugin_option[align]',
			'label'			=> __('Table align','easy-table'),
			'type'			=> 'text',
			'description'	=> __('Table align, left/right/center (may be overriden by CSS)','easy-table'),
			'value'			=> $this->get_easy_table_option('align'))
		,Array(
			'name'			=>'easy_table_plugin_option[border]',
			'label'			=> __('Table border','easy-table'),
			'type'			=> 'text',
			'description'	=> __('Table border (may be overriden by CSS)','easy-table'),
			'value'			=> $this->get_easy_table_option('border'))
	);
	?>	

	<h3><?php _e('Table options','easy-table');?></h3>
	<?php
		echo $this->render_form($fields);
	?>
		
	<h3><?php _e('Parser Option','easy-table');?></h3>
	<p><em><?php _e('Do not change this unless you know what you\'re doing','easy-table');?></em>
	</p>
	<?php
	$fields = Array(
		Array(
			'name'			=> 'easy_table_plugin_option[delimiter]',
			'label'			=> __('Delimiter','easy-table'),
			'type'			=> 'text',
			'value'			=> $this->get_easy_table_option('delimiter'),
			'description'	=> __('CSV delimiter (default is comma)','easy-table'))
		,Array(
			'name'			=> 'easy_table_plugin_option[enclosure]',
			'label'			=> __('Enclosure','easy-table'),
			'type'			=> 'text',
			'value'			=> htmlentities($this->get_easy_table_option('enclosure')),
			'description'	=> __('CSV enclosure (default is double quote)','easy-table'))
		,Array(	
			'name'			=> 'easy_table_plugin_option[escape]',
			'label'			=> __('Escape','easy-table'),
			'type'			=> 'text',
			'value'			=> $this->get_easy_table_option('escape'),
			'description'	=>__('CSV escape (default is backslash)','easy-table'))
		,Array(
			'name'			=> 'easy_table_plugin_option[csvfile]',
			'label'			=> __('Allow read CSV from file?','easy-table'),
			'type'			=> 'checkbox',
			'value'			=> 1,
			'description'	=> __('Check this if you also want to convert CSV file to table','easy-table'),
			'attr'			=> $this->get_easy_table_option('csvfile') ? 'checked="checked"' : '')
		);
		echo $this->render_form($fields);
	?>

<input type="hidden" name="action" value="update" />
<input type="hidden" name="easy_table_option_field" value="easy_table_plugin_option" />
<p><input type="submit" class="button-primary" value="<?php _e('Save','easy-table');?>" /> </p>
</form>
</div>
<div class="right">
<?php
$defaulttableexample = '
[table caption="Just test table"]
no[attr width="20"],head1,head2,head3
1,row1col1,row1col2,row1col3
2,row2col1,row2col2,row2col3
3,row3col1[attr colspan="2"],row3col3
4,row4col1,row4col2,row4col3
[/table]	';
$tableexample = $defaulttableexample;
if(isset($_POST['test-easy-table'])){
	$tableexample = $_POST['easy-table-test-area'];
}

if(isset($_POST['test-easy-table-reset'])){
	$tableexample = $defaulttableexample;
}
?>
<h3><?php _e('Possible parameter','easy-table');?></h3>
<p><?php _e('These parameters commonly can override global options in the left side of this page. Example usage:','easy-table');?></p>
<p> <code>[table param1="param-value1" param2="param-value2"]table data[/table]</code></p>
<ol>
<li><strong>class</strong>, <?php _e('default value','easy-table');?> <em>'table-striped'</em>, <?php _e('another value','easy-table');?> <em>table-bordered, table-striped, table-condensed</em></li>
<li><strong>caption</strong>,<?php _e('default value','easy-table');?> <em>''</em></li>
<li><strong>width</strong>, <?php _e('default value','easy-table');?> <em>'100%'</em></li>
<li><strong>align</strong>, <?php _e('default value','easy-table');?> <em>'left'</em></li>
<li><strong>th</strong>, <?php _e('default value','easy-table');?> <em>'true'</em></li>
<li><strong>tf</strong>, <?php _e('default value','easy-table');?> <em>'false'</em></li>
<li><strong>border</strong>, <?php _e('default value','easy-table');?> <em>'0'</em></li>
<li><strong>id</strong>, <?php _e('default value','easy-table');?> <em>'false'</em></li>
<li><strong>tablesorter</strong>, <?php _e('default value','easy-table');?> <em>'false'</em></li>
<li><strong>file</strong>, <?php _e('default value','easy-table');?> <em>'false'</em></li>
</ol>
<h3><?php _e('Cell attribute tag','easy-table');?></h3>
<ol>
	<li><strong>attr</strong>, <?php _e('To set attribute for cell eg. class, colspan, rowspan, etc','easy-table');?>
	<br /><?php _e('Usage','easy-table');?>: <br />

<pre><code>[table]
col1,col2[attr width="200" class="someclass"],col3
col4,col5,col6
[/table]
</code>
	</pre>
</li>
</ol>

<h3><?php _e('Test area:','easy-table');?></h3>
	<form action="" method="post">
	<textarea name="easy-table-test-area" style="width:500px;height:200px;border:1px solid #ccc"><?php echo trim(htmlentities(stripslashes($tableexample)));?>
	</textarea>
	<input type="hidden" name="test-easy-table" value="1" />
	<p><input class="button" type="submit" name="test-easy-table-reset" value="<?php _e('Reset','easy-table');?>" />
	<input class="button-primary" type="submit" value="<?php _e('Update preview','easy-table');?> &raquo;" /></p></form>
	<div>
	<h3><?php _e('Preview','easy-table');?></h3>
	<?php echo do_shortcode(stripslashes($tableexample));?>
	</div>

</div>
<div class="clear"></div>

<?php elseif($_GET['gettab'] == 'support') : ?>
<p><a target="_blank" href="http://takien.com/plugins/easy-table"><?php _e('Full documentation, see here!','easy-table');?></a></p>
<p><?php _e('Or you can use this discussion to get support, request feature or reporting bug.','easy-table');?></p>
<div id="disqus_thread"></div>
<script type="text/javascript">
/* <![CDATA[ */
    var disqus_url = 'http://takien.com/1126/easy-table-is-the-easiest-way-to-create-table-in-wordpress.php';
    var disqus_identifier = '1126 http://takien.com/?p=1126';
    var disqus_container_id = 'disqus_thread';
    var disqus_domain = 'disqus.com';
    var disqus_shortname = 'takien';
    var disqus_title = "Easy Table is The Easiest Way to Create Table in WordPress";
        var disqus_config = function () {
        var config = this; 
        config.callbacks.preData.push(function() {
            // clear out the container (its filled for SEO/legacy purposes)
            document.getElementById(disqus_container_id).innerHTML = '';
        });
                config.callbacks.onReady.push(function() {
            // sync comments in the background so we don't block the page
            DISQUS.request.get('?cf_action=sync_comments&post_id=1126');
        });
                    };
    var facebookXdReceiverPath = 'http://takien.com/wp-content/plugins/disqus-comment-system/xd_receiver.htm';
/* ]]> */
</script>

<script type="text/javascript">
/* <![CDATA[ */
    var DsqLocal = {
        'trackbacks': [
        ],
        'trackback_url': "http:\/\/takien.com\/1126\/easy-table-is-the-easiest-way-to-create-table-in-wordpress.php\/trackback"    };
/* ]]> */
</script>

<script type="text/javascript">
/* <![CDATA[ */
(function() {
    var dsq = document.createElement('script'); dsq.type = 'text/javascript';
    dsq.async = true;
        dsq.src = 'http' + '://' + disqus_shortname + '.' + disqus_domain + '/embed.js?pname=wordpress&pver=2.72';
    (document.getElementsByTagName('head')[0] || document.getElementsByTagName('body')[0]).appendChild(dsq);
})();
/* ]]> */
</script>
<?php elseif ($_GET['gettab'] == 'about') : ?>
<?php
require_once(ABSPATH.'wp-admin/includes/plugin-install.php');
$api = plugins_api('plugin_information', array('slug' => 'easy-table' ));
?>
 	<div>
	<h2 class="mainheader"><?php echo $this->easy_table_base('name') .' ' . $this->easy_table_base('version'); ?></h2>
		<?php if ( ! empty($api->download_link) && ( current_user_can('install_plugins') || current_user_can('update_plugins') ) ) : ?>
		<p class="action-button">
		<?php
		$status = install_plugin_install_status($api);
		switch ( $status['status'] ) {
			case 'install':
				if ( $status['url'] )
					echo '<a href="' . $status['url'] . '" target="_parent">' . __('Install Now') . '</a>';
				break;
			case 'update_available':
				if ( $status['url'] )
					echo '<a  class="red" href="' . $status['url'] . '" target="_parent">' . __('Install Update Now') .'</a>';
				break;
			case 'newer_installed':
				echo '<a class="green">' . sprintf(__('Newer Version (%s) Installed'), $status['version']) . '</a>';
				break;
			case 'latest_installed':
				echo '<a class="green">' . __('Latest Version Installed') . '</a>';
				break;
		}
		?>
		</p>
		<?php endif; ?>
		
		<ul>
<?php if ( ! empty($api->version) ) : ?>
			<li><strong><?php _e('Latest Version:','easy-table') ?></strong> <?php echo $api->version ?></li>
<?php endif; if ( ! empty($api->author) ) : ?>
			<li><strong><?php _e('Author:') ?></strong> <?php echo links_add_target($api->author, '_blank') ?></li>
<?php endif; if ( ! empty($api->last_updated) ) : ?>
			<li><strong><?php _e('Last Updated:') ?></strong> <span title="<?php echo $api->last_updated ?>"><?php
							printf( __('%s ago'), human_time_diff(strtotime($api->last_updated)) ) ?></span></li>
<?php endif; if ( ! empty($api->requires) ) : ?>
			<li><strong><?php _e('Requires WordPress Version:') ?></strong> <?php printf(__('%s or higher'), $api->requires) ?></li>
<?php endif; if ( ! empty($api->tested) ) : ?>
			<li><strong><?php _e('Compatible up to:') ?></strong> <?php echo $api->tested ?></li>
<?php endif; if ( ! empty($api->downloaded) ) : ?>
			<li><strong><?php _e('Downloaded:') ?></strong> <?php printf(_n('%s time', '%s times', $api->downloaded), number_format_i18n($api->downloaded)) ?></li>
<?php endif; if ( ! empty($api->slug) && empty($api->external) ) : ?>
			<li><a target="_blank" href="http://wordpress.org/extend/plugins/<?php echo $api->slug ?>/"><?php _e('WordPress.org Plugin Page &#187;') ?></a></li>
<?php endif; if ( ! empty($api->homepage) ) : ?>
			<li><a target="_blank" href="<?php echo $api->homepage ?>"><?php _e('Plugin Homepage  &#187;') ?></a></li>
<?php endif; ?>
		</ul>
		<?php if ( ! empty($api->rating) ) : ?>
		<h2><?php _e('Average Rating') ?></h2>
		<div class="star-holder" title="<?php printf(_n('(based on %s rating)', '(based on %s ratings)', $api->num_ratings), number_format_i18n($api->num_ratings)); ?>">
			<div class="star star-rating" style="width: <?php echo esc_attr($api->rating) ?>px"></div>
			<div class="star star5"><img src="<?php echo admin_url('images/star.png?v=20110615'); ?>" alt="<?php esc_attr_e('5 stars') ?>" /></div>
			<div class="star star4"><img src="<?php echo admin_url('images/star.png?v=20110615'); ?>" alt="<?php esc_attr_e('4 stars') ?>" /></div>
			<div class="star star3"><img src="<?php echo admin_url('images/star.png?v=20110615'); ?>" alt="<?php esc_attr_e('3 stars') ?>" /></div>
			<div class="star star2"><img src="<?php echo admin_url('images/star.png?v=20110615'); ?>" alt="<?php esc_attr_e('2 stars') ?>" /></div>
			<div class="star star1"><img src="<?php echo admin_url('images/star.png?v=20110615'); ?>" alt="<?php esc_attr_e('1 star') ?>" /></div>
		</div>
		<small><?php printf(_n('(based on %s rating)', '(based on %s ratings)', $api->num_ratings), number_format_i18n($api->num_ratings)); ?></small>
		<p><a target="_blank" href="http://wordpress.org/extend/plugins/easy-table/"><?php _e('Click here to rate','easy-table');?></a></p>
		<h3><?php _e('Credit','easy-table');?>:</h3>
<p><?php _e('Tablesorter by','easy-table');?> <a target="_blank" href="http://tablesorter.com">tablesorter</a>, <?php _e('CSS by','easy-table');?> <a target="_blank" href="http://twitter.github.com/bootstrap">Twitter Bootstrap</a></p>
		<?php endif; ?>
	</div>
<?php endif; ?>

</div><!--wrap-->

<?php
	}
			
} /* end class */
}
add_action('init', 'easy_table_init');
function easy_table_init() {
	if (class_exists('EasyTable')) {
		new EasyTable();
	}
}

/**
* Create function str_getcsv if not exists in server
* @since version 0.2
*/	
if (!function_exists('str_getcsv')) {
	function str_getcsv($input, $delimiter = ",", $enclosure = '"', $escape = "\\"){
		$fiveMBs = 5 * 1024 * 1024;
		if (($handle = fopen("php://temp/maxmemory:$fiveMBs", 'r+')) !== FALSE) {
		fputs($handle, $input);
		rewind($handle);
		$line = -1;
		$return = Array();
		while (($data = fgetcsv($handle, 1000, $delimiter)) !== FALSE) {
			$num = count($data);
			for ($c=0; $c < $num; $c++) {
			 if(!empty($data[$c])){ 
				$line++;
				$return[$line] = $data[$c];
			}
			}
		}
		fclose($handle);
		return $return;
		}
	}
}