<?php /*                         <!-- md_scorm.php for Dokeos metadata/*.php -->
                                                             <!-- 2005/09/20 -->

<!-- Copyright (C) 2005 rene.haentjens@UGent.be -  see metadata/md_funcs.php -->

*/

/**
============================================================================== 
*	Dokeos Metadata: class mdobject for Scorm-type objects
*
*	@package dokeos.metadata
============================================================================== 
*/

class mdobject
{

var $mdo_course;
var $mdo_type;
var $mdo_id;
var $mdo_eid;

var $mdo_dcmap_e;
var $mdo_dcmap_v;

var $mdo_path;
var $mdo_comment;
var $mdo_filetype;
var $mdo_url;


function mdo_define_htt() { return new xhtdoc(<<<EOD

<!-- {-INDEXABLETEXT-} -->

Title: {-V metadata/lom/general/title/string-} txt-sep
Keyword(s): {-R metadata/lom/general/keyword C KWTEXT-} txt-sep
 {-V metadata/lom/general/description[1]/string-}
 {-V metadata/lom/technical/location-} txt-end
 {-V metadata/lom/general/description[2]/string-} scorm-level-{-V @level-}
 {-V metadata/lom/lifeCycle/contribute[1]/entity-}
 {-V metadata/lom/lifeCycle/contribute[1]/date/dateTime-}


<!-- {-KWTEXT-} -->

 {-V string-}-kw


<!-- {--} -->
EOD
);
}


function mdo_generate_default_xml_metadata()
{
    return '<empty/>';
}


function mdo_add_breadcrump_nav()
{
	global $interbreadcrumb;
	$regs = array(); // for use with ereg()

	$docurl = $_SERVER['PHP_SELF'];  // should be .../main/xxx/yyy.php
	if (ereg('^(.+[^/\.]+)/[^/\.]+/[^/\.]+.[^/\.]+$', $docurl, $regs))
		$docurl = $regs[1] . '/scorm/scormdocument.php';

	$interbreadcrumb[] = array ('url' => $docurl, 
		'name' => get_lang('MdCallingTool'));
}


function mdobject($_course, $id)
{
    global $ieee_dcmap_e, $ieee_dcmap_v;  // md_funcs
    
    $scormdocument = Database::get_course_table(TABLE_SCORMDOC);
    
    $this->mdo_course = $_course; $this->mdo_type = 'Scorm';
    $this->mdo_id = $id; $this->mdo_eid = $this->mdo_type . '.' . $id;
    
    $this->mdo_dcmap_e = $ieee_dcmap_e; $this->mdo_dcmap_v = $ieee_dcmap_v;
    
    if (($docinfo = @mysql_fetch_array(api_sql_query(
            "SELECT path,comment,filetype FROM $scormdocument
             WHERE id='" . addslashes($id) . "'", 
            __FILE__, __LINE__))))
    {
        $this->mdo_path =     $docinfo['path'];
        $this->mdo_comment =  $docinfo['comment'];
        $this->mdo_filetype = $docinfo['filetype'];
    
        $this->mdo_url =  get_course_web() . $this->mdo_course['path'] . 
            '/scorm' . $this->mdo_path . '/index.php';
    }
}

}
?>