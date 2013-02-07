<?php
/**
 * @version		1.0.0 townwizard_container $
 * @package		townwizard_container
 * @copyright	Copyright © 2012 - All rights reserved.
 * @license		GNU/GPL
 * @author		MLS
 * @author mail	nobody@nobody.com
 *
 *
 */

// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controller');

class TownwizardControllerSection extends JController
{
    /*
    * Load section by id with sub-sections
    */
    public function section()
    {
        $model = $this->getModel('PartnerSection');
        $dbQuery = $model->getQuery();
        $dbQuery['fields'][] = 's.name as section_name';
        $dbQuery['conditions'] = sprintf('ps.id = %d OR ps.parent_id = %d', (int) $model->getId(), (int) $model->getId());
        $dbQuery['order'] = 'ps.parent_id ASC';

        $partnerSection = $this->_getSections($model->buildQuery($dbQuery));

        $response = array('status' => 0, 'error' => '', 'data' => array());
        if ($partnerSection)
        {
            $response['status'] = 1;
            $response['data'] = array_pop($partnerSection);
        }
        else
        {
            $response['status'] = 0;
            $response['error'] = 'Section not found';
        }
        header('Content-type: application/json');
        echo json_encode($response);
        exit();
    }

    /*
    * Load sections by partner id. Each section will include sub-sections.
    */
    public function partner()
    {
        $model = $this->getModel('PartnerSection');
        $dbQuery = $model->getQuery();
        $dbQuery['fields'][] = 's.name as section_name';
        $dbQuery['conditions'][] = 'ps.partner_id = ' . (int) JRequest::getVar('cid', 0);

        $partnerSections = $this->_getSections($model->buildQuery($dbQuery));

        $response = array('status' => 0, 'error' => '', 'data' => array());
        if ($partnerSections)
        {
            $response['status'] = 1;
            $response['data'] = array_values($partnerSections);
        }
        else
        {
            $response['status'] = 0;
            $response['error'] = 'There are no menu items found for this partner';
        }
        header('Content-type: application/json');
        echo json_encode($response);
        exit();
    }

    private function _getSections($sql)
    {
        $db =& JFactory::getDBO();
        $db->setQuery($sql);
        $pSections = $db->loadObjectList();

        $apiV = JRequest::getVar('api_version', '1.1');

        $partnerSections = array();
        foreach ($pSections as $partnerSection)
        {
            $ui_type = TablePartnerSection::$ui_types[$partnerSection->ui_type];
            if ($apiV == '2.1' || $apiV == '3.0')
            {
                $url = $ui_type == 'json' ? $partnerSection->json_api_url : $partnerSection->section_url;
                if ($apiV == '3.0')
                {
                    $android_ui_type = TablePartnerSection::$ui_types[$partnerSection->android_ui_type];
                    $android_url = $android_ui_type == 'json' ? $partnerSection->android_json_api_url : $partnerSection->android_url;
                }
            }
            else
            {
                $url = $partnerSection->section_url;
            }

            $ps = array(
                'id' => $partnerSection->id,
                'display_name' => stripslashes($partnerSection->display_name),
                'url' => $ui_type != 'none' ? $url : '',
                'image_url' => $partnerSection->image_url ? '/media/com_townwizard/images/sections/' . $partnerSection->image_url : '',
                'partner_id' => $partnerSection->partner_id,
                'section_name' => $partnerSection->section_name,
                'sub_sections' => array()
            );

            if ($apiV == '2.1' || $apiV == '3.0')
            {
                $ps['ui_type'] = $ui_type;
                if ($apiV == '3.0')
                {
                    $ps['android_url'] = $android_ui_type != 'none' ? $android_url : '';
                    $ps['android_ui_type'] = $android_ui_type;
                }
            }

            if (!$partnerSection->parent_id)
            {
                $partnerSections[$partnerSection->id] = $ps;
            }
            else if (isset($partnerSections[$partnerSection->parent_id]))
            {
                $partnerSections[$partnerSection->parent_id]['url'] = '';
                if (isset($partnerSections[$partnerSection->parent_id]['android_url']))
                {
                    $partnerSections[$partnerSection->parent_id]['android_url'] = '';
                }
                $partnerSections[$partnerSection->parent_id]['sub_sections'][] = $ps;
            }
        }
        /*
        foreach ($partnerSections as $key => $partnerSection)
        {
            if (!empty($partnerSection['sub_sections']))
            {
                $partnerSections[$key]['url'] = '';
            }
        }*/

        return $partnerSections;
    }
}
?>
