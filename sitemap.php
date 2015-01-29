<?php
defined('PHPWG_ROOT_PATH') or die('Hacking attempt!');

define('RVS_DIR' , basename(dirname(__FILE__)));
define('RVS_PATH' , PHPWG_PLUGINS_PATH . RVS_DIR . '/');
load_language('plugin.lang', RVS_PATH);

function sitemaps_get_config_file_name()
{
  global $conf;
  $dir = PHPWG_ROOT_PATH.$conf['data_location'].'plugins/';
  mkgetdir( $dir );
  return $dir.basename(dirname(__FILE__)).'.dat';
}

function start_xml($filename, $gzip)
{
  global $file;
  $file = fopen( $filename.($gzip?'.gz':''), 'w' );
  out_xml('<?xml version="1.0" encoding="UTF-8"?'.'>
<?xml-stylesheet type="text/xsl" href="'.get_root_url().'plugins/'.basename(dirname(__FILE__)).'/sitemap.xsl"?'.'>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">', $gzip );
}

function out_xml($xml, $gzip)
{
  global $file;
  if ($gzip)
    fwrite($file, gzencode($xml, 9) );
  else
    fwrite($file, $xml);
}

function end_xml($gzip)
{
  global $file;
  out_xml('</urlset>',$gzip );
  fclose( $file );
}

function add_url($url, $lastmod=null, $changefreq=null, $priority=null, $images_xml=null)
{
  $xml=
'<url>
 <loc>'.$url.'</loc>';

  if ( isset($lastmod) and strlen($lastmod)>0 )
  {
    if (strlen($lastmod)>11)
    {
      $lastmod[10] = 'T';
      if (strlen($lastmod)==19)
      	$lastmod .= '-05:00';
    }
    $xml.='
 <lastmod>'.$lastmod.'</lastmod>';
  }

  if ( isset($changefreq) and $changefreq!='' ) $xml.='
 <changefreq>'.$changefreq.'</changefreq>';

  if ( isset($priority) and $priority!='' ) $xml.='
 <priority>'.$priority.'</priority>';

  if ( isset($images_xml) )
    $xml .= "\n".$images_xml;

  $xml .= '
</url>';
global $gzip,$url_count;
  $url_count++;
  out_xml($xml, $gzip);
}



include_once(PHPWG_ROOT_PATH.'admin/include/functions.php');
check_status(ACCESS_ADMINISTRATOR);



$frequenciesT = array(
    '',
    l10n('always'),
    l10n('hourly'),
    l10n('daily'),
    l10n('weekly'),
    l10n('monthly'),
    l10n('yearly'),
    l10n('never'),
  );

$frequencies = array(
    '',
    'always',
    'hourly',
    'daily',
    'weekly',
    'monthly',
    'yearly',
    'never',
  );

$specials = array(
  'categories' => array('L'=>l10n('Home'), 'P'=>0.9),
  'best_rated' => array('L'=>l10n('Best rated'), 'P'=>0.8 ) ,
  'most_visited' => array('L'=>l10n('Most visited'), 'P'=>0.8 ),
  'recent_pics' => array('L'=>l10n('Recent photos'), 'P'=>0.8, 'F'=>'weekly' ),
  'tags' => array('L'=>l10n('Tags'), 'PAGE'=>'tags.php' , 'P'=>0.8 ),
  );

$url_count=0;

// BEGIN AS GUEST
$save_user = $user;
$user = build_user( $conf['guest_id'], true);

$query = '
SELECT id, name, permalink, uppercats, global_rank, IFNULL(date_last, max_date_last) AS date_last
  FROM '.CATEGORIES_TABLE.' INNER JOIN '.USER_CACHE_CATEGORIES_TABLE.'
  ON id = cat_id AND user_id = '.$conf['guest_id'].'
  WHERE max_date_last IS NOT NULL
  ORDER BY global_rank';
$categories = array_from_query($query);
usort($categories, 'global_rank_compare');

$tags = get_available_tags();
usort($tags, 'name_compare');

if ( isset($_POST['submit']) )
{
  // echo('<pre>'.var_export($_POST,true).'</pre>' );

  if ( $_POST['filename'] != '' )
    $filename = $_POST['filename'];
  $gzip = @$_POST['gzip']=='on' ? true : false;

  $selected_specials = array();
  foreach ($specials as $key=>$value)
  {
    if ( @$_POST['special_'.$key]=='on' )
      array_push( $selected_specials, $key );
    $specials[$key]['P'] = $_POST['special_prio_'.$key];
    $specials[$key]['F'] = $_POST['special_freq_'.$key];
  }
  if ( isset($_POST['categories']) )
    $selected_categories = $_POST['categories'];
  else
    $selected_categories = array();
  $prio_categories = $_POST['prio_categories'];
  $freq_categories = $_POST['freq_categories'];

  if ( isset($_POST['tags']) )
    $selected_tags = $_POST['tags'];
  else
    $selected_tags = array();
  $prio_tags = $_POST['prio_tags'];
  $freq_tags = $_POST['freq_tags'];

  $photo_count = intval($_POST['photo_count']);

  set_make_full_url();

  start_xml($filename, $gzip);

  $r_selected_specials = array_flip($selected_specials);
  foreach ($specials as $key=>$value)
  {
    if (! isset ($r_selected_specials[$key]) )
      continue;
    if ( isset($value['PAGE']) )
      $url = get_root_url().$value['PAGE'];
    else
      $url = make_index_url( array('section'=>$key) );
    add_url($url, null, $value['F'], $value['P'] );
  }


  $r_selected_categories = array_flip($selected_categories);
  foreach ($categories as $cat)
  {
    if (! isset ($r_selected_categories[$cat['id']]) )
      continue;

    $url = make_index_url(
            array(
              'category'=>$cat
            )
        );
    add_url($url, $cat['date_last'], $freq_categories, $prio_categories);
  }

  $r_selected_tags = array_flip($selected_tags);
  if ( !empty($selected_tags) )
  {
    $query = 'SELECT tag_id, MAX(date_available) AS da, MAX(date_creation) AS dc, MAX(date_metadata_update) AS dm
  FROM '.IMAGE_TAG_TABLE.' INNER JOIN '.IMAGES_TABLE.' ON image_id=id
  WHERE tag_id IN ('.implode(',',$selected_tags).')
  GROUP BY tag_id';
    $result = pwg_query($query);
    $tag_infos = array();
    while ($row = pwg_db_fetch_assoc($result))
    {
      $tag_infos[$row['tag_id']]= max( $row['da'],$row['dc'],$row['dm']);
    }

    foreach( $tags as $tag)
    {
      if (!isset($r_selected_tags[ $tag['id'] ] ) )
        continue;

      $url = make_index_url(
            array(
              'section'=>'tags',
              'tags'=> array( $tag )
            )
        );

      add_url($url, $tag_infos[ $tag['id'] ], $freq_tags, $prio_tags);
    }
  }

	$selected_derivatives = array();
  if ($photo_count > 0)
  {
		if (isset($_POST['selected_derivatives']))
			$selected_derivatives = $_POST['selected_derivatives'];

		$selected_derivatives_params = array();
		foreach($selected_derivatives as $type)
			$selected_derivatives_params[] = ImageStdParams::get_by_type($type);

    $query = 'SELECT DISTINCT i.* FROM '.IMAGES_TABLE.' i
  INNER JOIN '.IMAGE_CATEGORY_TABLE.' on i.id=image_id
'.get_sql_condition_FandF( array('forbidden_categories' => 'category_id', 'forbidden_images'=>'i.id'), 'WHERE ' ).'
  ORDER BY date_available DESC
  LIMIT '.$photo_count;
    $result = pwg_query($query);
    while ($row = pwg_db_fetch_assoc($result))
    {
      $url = make_picture_url( array(
        'image_id' => $row['id'],
        'image_file' => $row['file'],
        ) );
      $src_image = new SrcImage($row);
      $images_xml = '';
			$done_iurls=array();
			foreach( $selected_derivatives_params as $params )
      {
        $deriv_url = DerivativeImage::url($params, $src_image);
				if (!isset($done_iurls[$deriv_url]))
				{
					$done_iurls[$deriv_url] = 1;
					$images_xml .= '<image:image><image:loc>'.$deriv_url.'</image:loc></image:image>';
				}
      }
      add_url($url, $row['date_available'], null, null, $images_xml);
    }
  }
  unset_make_full_url();
  end_xml($gzip);

  $page['infos'][] = $url_count.' urls saved';

  // save the data for future use
  $selected_tag_urls = array();
  foreach( $tags as $tag)
  {
    if (isset($r_selected_tags[ $tag['id'] ] ) )
      array_push($selected_tag_urls, $tag['url_name']);
  }
  $x = compact( 'filename', 'selected_tag_urls', 'prio_tags', 'freq_tags',
  'selected_categories', 'prio_categories', 'freq_categories',
  'selected_specials', 'photo_count', 'selected_derivatives' );
  $file = fopen( sitemaps_get_config_file_name(), 'w' );
  fwrite($file, serialize($x) );
  fclose( $file );
}
else
{
  $filename = 'sitemap.xml';
  $gzip = false;
  $selected_specials = 'all';
  $prio_categories = 0.5;
  $prio_tags = 0.6;
  $freq_categories = 'monthly';
  $freq_tags = 'monthly';
  $photo_count = 0;
	$selected_derivatives = array();

  $conf_file_name = sitemaps_get_config_file_name();
  $old_file = dirname(__FILE__).'/_sitemap.dat';
  if (file_exists($old_file) and !file_exists($conf_file_name) )
  {
    copy($old_file, $conf_file_name);
    unlink($old_file);
  }

  $x = @file_get_contents( $conf_file_name );
  if ($x!==false)
  {
    $x = unserialize($x);
    extract($x);
    $selected_tags = array();
    if (isset($selected_tag_urls))
    {
      foreach($tags as $tag)
      {
        if ( in_array($tag['url_name'], $selected_tag_urls ) )
          array_push($selected_tags, $tag['id']);
      }
      unset($selected_tag_urls);
    }
  }

  if (!is_array(@$selected_categories)) $selected_categories = array();
  if (!is_array(@$selected_tags)) $selected_tags = array();
}

// END AS GUEST
$user = $save_user;


$template->assign( array(
  'FILENAME' => $filename,
  'U_FILENAME' => get_root_url().$filename.($gzip?'.gz':''),
  'GZIP_CHECKED' => $gzip ? 'checked="checked"' : '',
  'PRIO_CATEGORIES' => $prio_categories,
  'PRIO_TAGS' => $prio_tags,
  'PHOTO_COUNT' => $photo_count,
    )
  );

foreach( $specials as $key=>$value)
{
  $checked='';
  if ($selected_specials=='all' or in_array($key, $selected_specials) )
    $checked = 'checked="checked"';

  $template->append( 'specials',
    array(
      'NAME' => $key,
      'LABEL' =>  $value['L'],
      'CHECKED' => $checked,
      'PRIO' => $value['P'],
      'FREQ' => isset($value['F']) ? $value['F'] : 'monthly',
      )
    );
}

display_select_categories($categories, $selected_categories, 'categories', false );
$template->assign('freq_categories_selected', $freq_categories);


foreach( $tags as $tag)
  $template->append( 'tags', array($tag['id']=>$tag['name']), true );
$template->assign('tags_selected', $selected_tags);
$template->assign('freq_tags_selected', $freq_tags);


$template->assign('frequencies', $frequencies);
$template->assign('frequenciesT', $frequenciesT);

$available_derivatives = array();
foreach(array_keys(ImageStdParams::get_defined_type_map()) as $type)
{
	$available_derivatives[$type] = l10n($type);
}
$template->assign( array('available_derivatives'=>$available_derivatives, 'selected_derivatives' => $selected_derivatives));

$template->set_filename('sitemap', dirname(__FILE__).'/sitemap.tpl');
$template->assign_var_from_handle('ADMIN_CONTENT', 'sitemap');

?>