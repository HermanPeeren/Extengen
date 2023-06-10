<?php

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;

echo "<hr>Here you can show a headertext<hr>";

if ($this->item->params->get('show_name')) {
	if ($this->Params->get('show_extengen_name_label')) {
		echo Text::_('COM_EXTENGEN_PROJECT_NAME') . $this->item->name;
	} else {
		echo $this->item->name;
	}
}

echo $this->item->event->afterDisplayTitle;
echo $this->item->event->beforeDisplayContent;
echo $this->item->event->afterDisplayContent;
