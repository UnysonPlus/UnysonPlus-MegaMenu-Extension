<?php if (!defined('FW')) die('Forbidden');

$cfg = array();

// Medium everywhere: every type now carries the Icon Position image-picker (4
// tiles), which needs a roomier modal to sit on one row; columns/items also hold
// image/content editors.
$cfg['item-options:popup-size:row']     = 'medium'; // small, medium, large
$cfg['item-options:popup-size:column']  = 'medium';
$cfg['item-options:popup-size:item']    = 'medium';
$cfg['item-options:popup-size:default'] = 'medium';
