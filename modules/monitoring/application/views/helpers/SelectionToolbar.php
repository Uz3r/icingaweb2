<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

class Zend_View_Helper_SelectionToolbar extends Zend_View_Helper_Abstract
{
    /**
     * Create a selection toolbar
     *
     * @param      $type
     * @param null $target
     *
     * @return string
     */
    public function selectionToolbar($type, $target = null)
    {
        return '';
        if ($type == 'multi') {
            return '<div class="selection-toolbar">'
                . '<a href="' . $target . '" data-base-target="_next"> Show All </a> </div>';
        } else {
            return '';
        }
    }
}
