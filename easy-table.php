<?php
/*
Plugin Name: Easy Table
Plugin URI: http://takien.com/
Description: Create table in post, page, or widget in easy way.
Author: Takien
Version: 0.2
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
	'tablewidget'	=> false,
	'class'			=> 'table-striped',
	'caption'		=> false,
	'width'			=> '100%',
	'align'			=> 'left',
	'th'			=> true,
	'border'		=> 0,
	'id'			=> false,
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
	if($this->get_easy_table_option('tablewidget')){
		add_filter('widget_text', 		'do_shortcode');
	}
}


private function easy_table_base($return){
	$easy_table_base = Array(
				'name' 			=> 'Easy Table',
				'version' 		=> '0.2',
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
		'border'		=> $this->get_easy_table_option('border'),
		'id'	  		=> $this->get_easy_table_option('id'),
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
	$output = '<table '.($id ? 'id="'.$id.'"':'').' width="'.$width.'" align="'.$align.'" class="table '.($tablesorter ? 'tablesorter ':'').$class.'" '.(($border !=='0') ? 'border="'.$border.'"' : '').'>';
	$output .= ($caption !=='') ? '<caption>'.$caption.'</caption>' : '';
	$output .= $th ? '<thead>' : '<tbody>';
	foreach($data as $k=>$v){ $i++;
		$v = array_pad($v,$max_cols,'');
		$output .= "\r\n".'<tr>';
		if($i==1){
			$thtd = $th ? 'th' : 'td';
			$output .= "<$thtd>".implode("</$thtd><$thtd>",array_values($v))."</$thtd>";
		}
		else{
			$output .= '<td>'.implode("</td><td>",array_values($v)).'</td>';
		}
		$output .= '</tr>';
		$output .= (($i==1) AND $th) ? '</thead><tbody>' : '';
	}
	$output .= $th ? '</tbody>' : '';
	$output .= '</table>';
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
function get_easy_table_option($return=''){
	$option = get_option('easy_table_plugin_option');
	if($return){
		return ($option[$return] !== '') ? $option[$return] : $this->settings[$return];
	}
	else{
		return $option;
	}
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
			$output .= '<td><input type="checkbox" id="'.$field['name'].'" name="'.$field['name'].'" value="'.$field['value'].'" '.$field['attr'].' />';
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
	if($this->get_easy_table_option('tablesorter')) {
		wp_enqueue_script('jquery');
		wp_register_script('easy_table_script',plugins_url( 'jquery.tablesorter.min.js' , __FILE__ ),'jquery');
		wp_enqueue_script('easy_table_script');
	}
}

/**
* Register stylesheet
*/	
function easy_table_style() {
	if($this->get_easy_table_option('loadcss')) {
		wp_register_style('easy_table_style', plugins_url('easy-table-style.css', __FILE__),false,$this->easy_table_base('version'));
		wp_enqueue_style( 'easy_table_style');
	}
}




function easy_table_admin_script(){
$page = isset($_GET['page']) ? $_GET['page'] : '';
if($page == $this->easy_table_base('plugin-domain')) { 
if($this->get_easy_table_option('tablesorter')) { ?>
<script src="<?php echo plugins_url( 'jquery.tablesorter.min.js' , __FILE__);?>"></script>
<?php }
if($this->get_easy_table_option('loadcss')) { ?>
<link rel="stylesheet" href="<?php echo  plugins_url('easy-table-style.css', __FILE__);?> " />
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
</style>
<script type="text/javascript">
//<![CDATA[
	jQuery(document).ready(function($){
		$('.toggledesc a').click(function(e){
			$('span.description').toggle();
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
		<h2><?php printf(__('%s Option','easy-table'), $this->easy_table_base('name'));?></h2>
<div class="left">
<form method="post" action="options.php">
<?php 
wp_nonce_field('update-options'); 
settings_fields('easy_table_option_field');

?>
	<span class="toggledesc"><em><a href="#"><?php _e('Show/hide description');?></a></em></span>
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
		,Array(
			'name'			=> 'easy_table_plugin_option[tablewidget]',
			'label'			=> __('Also render table in widget?','easy-table'),
			'type'			=> 'checkbox',
			'description'	=> __('Check this if you want the table could be rendered in widget.','easy-table'),
			'value'			=> 1,
			'attr'			=> $this->get_easy_table_option('tablewidget') ? 'checked="checked"' : '')
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
$defaulttableexample = '[table caption="Just test table"]
row1col1,row1col2,row1col3
row2col1,row2col2,row2col3
row3col1,row3col2,row3col3
[/table]';
$tableexample = $defaulttableexample;
if(isset($_POST['test-easy-table'])){
	$tableexample = $_POST['easy-table-test-area'];
}

if(isset($_POST['test-easy-table-reset'])){
	$tableexample = $defaulttableexample;
}
?>
<h3><?php _e('Possible parameter','easy-table');?></h3>
<p><?php _e('These parameters commonly can override global options in the left side of this page. Example usage:','easy-table');?></p><p> <code>[table param1="param-value1" param2="param-value2"]table data[/table]</code></p>
<ol>
<li><strong>class</strong>, <?php _e('default value','easy-table');?> <em>'table-striped'</em>, <?php _e('another value','easy-table');?> <em>table-bordered, table-striped, table-condensed</em></li>
<li><strong>caption</strong>,<?php _e('default value','easy-table');?> <em>''</em></li>
<li><strong>width</strong>, <?php _e('default value','easy-table');?> <em>'100%'</em></li>
<li><strong>align</strong>, <?php _e('default value','easy-table');?> <em>'left'</em></li>
<li><strong>th</strong>, <?php _e('default value','easy-table');?> <em>'true'</em></li>
<li><strong>border</strong>, <?php _e('default value','easy-table');?> <em>'0'</em></li>
<li><strong>id</strong>, <?php _e('default value','easy-table');?> <em>'false'</em></li>
<li><strong>tablesorter</strong>, <?php _e('default value','easy-table');?> <em>'false'</em></li>
<li><strong>file</strong>, <?php _e('default value','easy-table');?> <em>'false'</em></li>
</ol>
<h3><?php _e('Test area:','easy-table');?></h3>
	<form action="" method="post">
	<textarea name="easy-table-test-area" style="width:500px;height:200px;border:1px solid #ccc"><?php echo trim(htmlentities(stripslashes($tableexample)));?>
	</textarea>
	<input type="hidden" name="test-easy-table" value="1" />
	<p><input class="button" type="submit" name="test-easy-table-reset" value="<?php _e('Reset','easy-table');?>" />
	<input class="button-primary" type="submit" value="<?php _e('Update preview','easy-table');?>' &raquo;" /></p></form>
	<div>
	<h3><?php _e('Preview','easy-table');?></h3>
	<?php echo do_shortcode(stripslashes($tableexample));?>
	</div>

</div>
<div class="clear"></div>
<p><a href="http://takien.com/plugins/easy-table"><?php _e('Any question or suggestion? Click here!','easy-table');?></a></p>
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