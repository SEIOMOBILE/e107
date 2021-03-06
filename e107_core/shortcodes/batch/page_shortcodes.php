<?php
/*
* Copyright e107 Inc e107.org, Licensed under GNU GPL (http://www.gnu.org/licenses/gpl.txt)
* $Id$
*
* Custom Pages shortcode batch
*/

if (!defined('e107_INIT')) { exit; }

/**
 *	@package    e107
 *	@subpackage	shortcodes
 *	@version 	$Id$
 *
 *	Shortcodes for custom page display
 */
class cpage_shortcodes extends e_shortcode
{
	// var $var; // parsed DB values
	private $chapterData = array();
	// Grab all book/chapter data. 
	function __construct()
	{
		
		$books = e107::getDb()->retrieve("SELECT * FROM #page_chapters ORDER BY chapter_id ASC" , true);
				
		foreach($books as $row)
		{
			$id 							= $row['chapter_id'];
			$this->chapterData[$id]			= $row;
		}	
	
	}
	
	// Return data for a specific chapter-id
	function getChapter()
	{
		$id = $this->page['page_chapter'];
		
		if(vartrue($this->chapterData[$id]['chapter_id']) && $this->chapterData[$id]['chapter_parent'] > 0)
		{
			return $this->chapterData[$id];	
		}
		return false;
	}
	
	
	// Return data for a specific book-id
	function getBook()
	{
		$pid = $this->page['page_chapter'];
		$cid = $this->chapterData[$pid]['chapter_parent'];
		
		$row = $this->chapterData[$cid];
		
		if(vartrue($row['chapter_id']) && $row['chapter_parent'] < 1)
		{
			return $row;	
		}
		
		return false; // not a book. 
		
	}
	
	
	
	// ----------------- Shortcodes ---------------------------------------

	function sc_cpagetitle($parm='')
	{
		return e107::getParser()->toHTML($this->getParserVars()->title, true, 'TITLE');
	}
	
	function sc_cpagesubtitle()
	{
		$subtitle = $this->getParserVars()->sub_title;
		return $subtitle ? e107::getParser()->toHTML($subtitle, true, 'TITLE') : '';
	}


	function sc_cpagebody($parm='')
	{
		// already parsed
		return $this->getParserVars()->text;
	}

	function sc_cpageauthor($parm)
	{
		$parms = eHelper::scParams($parm);
		$author = '';
		$url = e107::getUrl()->create('user/profile/view', array('name' => $this->page['user_name'], 'id' => $this->page['user_id']));
		
		if(isset($parms['url']))
		{
			return $url;
		}
		
		if($this->page['page_author'])
		{
			// currently this field used as Real Name, no matter what the db name says
			if($this->page['user_login'] && !isset($parms['user'])) $author = $this->page['user_login'];
			elseif($this->page['user_name']) $author = preg_replace('/[^\w\pL\s]+/u', ' ', $this->page['user_name']);
		}
		
		if(empty($author)) return '';
		
		
		
		if(isset($parms['nolink']))
		{
			return $author;
		}
		//TODO title lan
		return '<a class="cpage-author" href="'.$url.'" title="">'.$author.'</a>';
	}

	function sc_cpagedate($parm)
	{
		if(empty($parm))
		{
			return e107::getDateConvert()->convert_date($this->page['page_datestamp'], 'long');
		}
		return e107::getDateConvert()->convert_date($this->page['page_datestamp'], $parm);
	}

	function sc_cpageid()
	{
		return $this->page['page_id'];
	}

	function sc_cpageanchor()
	{
		$frm = e107::getForm();
		return $frm->name2id($this->page['page_title']);
	}

	// Not a shortcode really, as it shouldn't be cached at all :/
	function cpagecomments()
	{
		$com = $this->getParserVars()->comments;
		//if($parm && isset($com[$parm])) return $com[$parm];
		return $com['comment'].$com['comment_form'];
	}
	
	function sc_cpagenav()
	{
		return $this->getParserVars()->np;
	}
	
	function sc_cpagerating()
	{
		return $this->getParserVars()->rating;
	}
	
	function sc_cpagemessage()
	{
		return e107::getMessage()->render();
	}

	/**
	 * Auto-thumbnailing now allowed.
	 * New sc parameter standards
	 * Exampes: 
	 * - {CPAGETHUMBNAIL=e_MEDIA/images/someimage.jpg|type=tag&w=200} render link with thumbnail max width 200px
	 * - {CPAGETHUMBNAIL=images/someimage.jpg|w=200} same as above
	 * - {CPAGETHUMBNAIL=images/someimage.jpg|type=src&aw=200&ah=200} return thumb link only, size forced to 200px X 200px (smart thumbnailing close to how Facebook is doing it)
	 * 
	 * @see eHelper::scDualParams()
	 * @see eHelper::scParams()
	 */
	function sc_cpagethumbnail($parm = '')
	{
		$parms = eHelper::scDualParams($parm);
		if(empty($parms[1])) return '';
		
		$tp = e107::getParser();
		$path = rawurldecode($parms[1]);
		
		if(substr($path, 0, 2) === 'e_') $path = str_replace($tp->getUrlConstants('raw'), $tp->getUrlConstants('sc'), $path);
		elseif($path[0] !== '{') $path = '{e_MEDIA}'.$path;
		
		$thumb = $tp->thumbUrl($path);
		$type = varset($parms[2]['type'], 'tag');

		switch($type)
		{
			case 'src':
				return $thumb;
			break;

			case 'link':
				return '<a href="'.$tp->replaceConstants($path, 'abs').'" class="cpage-image" rel="external image"><img class="cpage-image" src="'.$src.'" alt="'.varset($parms[1]['alt']).'" /></a>';
			break;

			case 'tag':
			default:
				return '<img class="cpage-image" src="'.$thumb.'" alt="'.varset($parms[1]['alt']).'" />';
			break;
		}
	}
	
	// For Future Use..
	function sc_cpageimage($parm = '')
	{
		list($num,$size) = explode("|",$parm);
		if($this->page['page_images'])
		{
			$img = explode(",",$this->page['page_images']);
			
		}	
	}

	function sc_cpagelink($parm)
	{
		$url = $this->sc_cpageurl();
		
		if($parm == 'href' || !$url)
		{
			return $url;
		}
		return '<a class="cpage" href="'.$url.'">'.$this->sc_cpagetitle().'</a>';
	}
	
	function sc_cpagebutton($parm)
	{
		$url = $this->sc_cpageurl();
		
		if($parm == 'href' || !$url)
		{
			return $url;
		}
		
		if(trim($this->page['page_text']) == '') // Hide the button when there is no page content. (avoids templates with and without buttons)
		{
			return "<!-- Button Removed: No page text exists! -->";	
		}
		
		parse_str($parm,$options);
		
		$text = vartrue($options['text'], LAN_READ_MORE);
		$size = vartrue($options['size'], "");
		$inc = ($size) ? " btn-".$size : "";
		
		return '<a class="cpage btn btn-primary btn-cpage'.$inc.'" href="'.$url.'">'.$text.'</a>';
	}	
	
	
	function sc_cmenutitle($parm='')
	{
		$tp 	= e107::getParser(); 
	//
		return $tp->toHTML($this->page['menu_title'], true, 'TITLE');
	}	


	function sc_cmenubody($parm='')
	{
		// print_a($this);
		return e107::getParser()->toHTML($this->page['menu_text'], true, 'BODY');
	}
	
	
	function sc_cmenuimage($parm='')
	{
		$tp = e107::getParser();
		
		if($video = $tp->toVideo($this->page['menu_image']))
		{
			return $video;	
		}
		
		$img = $tp->thumbUrl($this->page['menu_image']);
		if($parm == 'url')
		{
			return $img;	
		}
		
		return "<img class='img-responsive' src='".$img."' alt='' />";
	}
	
	function sc_cmenuicon($parm='')
	{
		return e107::getParser()->toIcon($this->page['menu_icon'], array('space'=>' '));
	}		


	function sc_cpageurl()
	{
		$route = ($this->page['page_chapter'] == 0) ? 'page/view/other' : 'page/view';
		
		return e107::getUrl()->create($route, $this->page, array('allow' => 'page_sef,page_title,page_id,chapter_sef,book_sef'));
	}
	
	function sc_cpagemetadiz()
	{
  		return $this->page['page_metadscr'];
	}
	
	function sc_cpagesef()
	{
  		return vartrue($this->page['page_sef'],'page-no-sef');
	}	
	
	// -------------------- Book - specific to the current page. -------------------------
	
	function sc_book_name()
	{
		$tp = e107::getParser();
		$row = $this->getBook();

		return $tp->toHtml($row['chapter_name'], false, 'TITLE');		
	}
	
	function sc_book_anchor()
	{
		$frm = e107::getForm();
		$row = $this->getBook();
		
		return $frm->name2id($row['chapter_name']);
	}
	
	function sc_book_icon()
	{
		$tp = e107::getParser();
		$row = $this->getBook();
		
		return $tp->toIcon($row['chapter_icon'], array('space'=>' '));
	}
	
	function sc_book_description()
	{
		$tp = e107::getParser();
		$row = $this->getBook();
		
		return $tp->toHtml($row['chapter_meta_description'], true, 'BODY');
	}
	
	
	
	// -------------------- Chapter - specific to the current page. -------------------------
	
	
	function sc_chapter_name()
	{
		$tp = e107::getParser();
		$row = $this->getChapter();

		return $tp->toHtml($row['chapter_name'], false, 'TITLE');		
	}
	
	function sc_chapter_anchor()
	{
		$frm = e107::getForm();
		$row = $this->getChapter();
		
		return $frm->name2id($row['chapter_name']);
	}
	
	function sc_chapter_icon()
	{
		$tp = e107::getParser();
		$row = $this->getChapter();
		
		return $tp->toIcon($row['chapter_icon']);
	}
	
	function sc_chapter_description()
	{
		$tp = e107::getParser();
		$row = $this->getChapter();
		
		return $tp->toHtml($row['chapter_meta_description'], true, 'BODY');
	}
	
		
	
	function sc_cpagerelated($array=array())
	{
		if(!varset($array['types']))
		{
			$array['types'] = 'page,news';
		}
			
		return e107::getForm()->renderRelated($array, $this->page['page_metakeys'], array('page'=>$this->page['page_id']));	
	}
	
	
	
	
	
}
