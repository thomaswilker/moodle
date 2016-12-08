<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package   theme_ouaclean
 * @copyright 2015 Open Universities Australia
 * @author    Ben Kelada (ben.kelada@open.edu.au)
 */
defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    // Logo file setting.
    $name = 'theme_ouaclean/logo';
    $title = get_string('logo', 'theme_ouaclean');
    $description = get_string('logodesc', 'theme_ouaclean');
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'logo');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    // Logo file setting.
    $name = 'theme_ouaclean/appleicon';
    $title = get_string('appleicon', 'theme_ouaclean');
    $description = get_string('appleicondesc', 'theme_ouaclean');
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'appleicon');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);    // Logo file setting.

    // Fav icon setting.
    $name = 'theme_ouaclean/favicon';
    $title = get_string('favicon', 'theme_ouaclean');
    $description = get_string('favicondesc', 'theme_ouaclean');
    $setting = new admin_setting_configstoredfile($name, $title, $description, 'favicon');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    // Custom CSS file.
    $name = 'theme_ouaclean/customcss';
    $title = get_string('customcss', 'theme_ouaclean');
    $description = get_string('customcssdesc', 'theme_ouaclean');
    $default = '';
    $setting = new admin_setting_configtextarea($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    // Footnote setting.
    $name = 'theme_ouaclean/footnote';
    $title = get_string('footnote', 'theme_ouaclean');
    $description = get_string('footnotedesc', 'theme_ouaclean');
    $default = '';
    $setting = new admin_setting_confightmleditor($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    // Custom footer setting.
    $name = 'theme_ouaclean/customfooter';
    $title = get_string('customfooter', 'theme_ouaclean');
    $description = get_string('customfooterdesc', 'theme_ouaclean');
    $default = '';
    $setting = new admin_setting_configtextarea($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    // Footnote setting.
    $name = 'theme_ouaclean/customheadermenu';
    $title = get_string('customheadermenu', 'theme_ouaclean');
    $description = get_string('customheadermenudesc', 'theme_ouaclean');
    $default = '';
    $setting = new admin_setting_configtextarea($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    // Custom Theme colours.

    $name = 'theme_ouaclean/less_btn_link_primary';
    $title = get_string('less_btn_link_primary', 'theme_ouaclean');
    $description = get_string('less_btn_link_primary_desc', 'theme_ouaclean');
    $default = '#0067ac';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    $name = 'theme_ouaclean/less_btn_link_hover';
    $title = get_string('less_btn_link_hover', 'theme_ouaclean');
    $description = get_string('less_btn_link_hover_desc', 'theme_ouaclean');
    $default = '#f79440';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    $name = 'theme_ouaclean/less_link_light_default';
    $title = get_string('less_link_light_default', 'theme_ouaclean');
    $description = get_string('less_link_light_default_desc', 'theme_ouaclean');
    $default = '#ffffff';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    $name = 'theme_ouaclean/less_link_light_hover';
    $title = get_string('less_link_light_hover', 'theme_ouaclean');
    $description = get_string('less_link_light_hover_desc', 'theme_ouaclean');
    $default = '#b9e0f7';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    $name = 'theme_ouaclean/less_link_hover';
    $title = get_string('less_link_hover', 'theme_ouaclean');
    $description = get_string('less_link_hover_desc', 'theme_ouaclean');
    $default = '#00355f';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    $name = 'theme_ouaclean/less_section_light_bg';
    $title = get_string('less_section_light_bg', 'theme_ouaclean');
    $description = get_string('less_section_light_bg_desc', 'theme_ouaclean');
    $default = '#f7f7f7';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    $name = 'theme_ouaclean/less_section_dark_bg';
    $title = get_string('less_section_dark_bg', 'theme_ouaclean');
    $description = get_string('less_section_dark_bg_desc', 'theme_ouaclean');
    $default = '#00355f';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    $name = 'theme_ouaclean/less_page_background';
    $title = get_string('less_page_background', 'theme_ouaclean');
    $description = get_string('less_page_background_desc', 'theme_ouaclean');
    $default = '#f7f7fa';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    $name = 'theme_ouaclean/less_header';
    $title = get_string('less_header', 'theme_ouaclean');
    $description = get_string('less_header_desc', 'theme_ouaclean');
    $default = '#00355f';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    $name = 'theme_ouaclean/less_header_border';
    $title = get_string('less_header_border', 'theme_ouaclean');
    $description = get_string('less_header_border', 'theme_ouaclean');
    $default = '#002645';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    $name = 'theme_ouaclean/less_keyline';
    $title = get_string('less_keyline', 'theme_ouaclean');
    $description = get_string('less_keyline_desc', 'theme_ouaclean');
    $default = '#ebeced';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    $name = 'theme_ouaclean/less_bodycopy';
    $title = get_string('less_bodycopy', 'theme_ouaclean');
    $description = get_string('less_bodycopy_desc', 'theme_ouaclean');
    $default = '';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    $name = 'theme_ouaclean/less_progressbar';
    $title = get_string('less_progressbar', 'theme_ouaclean');
    $description = get_string('less_progressbar_desc', 'theme_ouaclean');
    $default = '#cce192';
    $setting = new admin_setting_configtext($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);



    // **************************************************************************   /

    $name = 'theme_ouaclean/less_extra';
    $title = get_string('less_extra', 'theme_ouaclean');
    $description = get_string('less_extra_desc', 'theme_ouaclean');
    $default = '';
    $setting = new admin_setting_configtextarea($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);
}
