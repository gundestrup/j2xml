<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_j2xml
 *
 * @version     __DEPLOY_VERSION__
 *
 * @author      Helios Ciancio <info (at) eshiol (dot) it>
 * @link        https://www.eshiol.it
 * @copyright   Copyright (C) 2010 - 2026 Helios Ciancio. All Rights Reserved
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL v3
 * J2XML is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License
 * or other free or open source software licenses.
 */

// no direct access
\defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Layout\LayoutHelper;

extract($displayData);

/** @var \Joomla\CMS\Form\Form $form */
/** @var string $tabSetId */
/** @var string $ui */

$fieldsets = $form->getFieldsets();

echo HTMLHelper::_($ui . '.startTabSet', $tabSetId, ['active' => 'export']);

foreach ($fieldsets as $name => $fieldSet)
{
	if ($name === 'details')
	{
		continue;
	}

	$label = empty($fieldSet->label) ? 'COM_J2XML_' . $name . '_FIELDSET_LABEL' : $fieldSet->label;
	echo HTMLHelper::_($ui . '.addTab', $tabSetId, $name, Text::_($label));

	foreach ($form->getFieldset($name) as $field)
	{
		$dataShowOn = '';
		$groupClass = $field->type === 'Spacer' ? ' field-spacer' : '';

		if ($field->showon)
		{
			$dataShowOn = ' data-showon=\'' . json_encode(FormHelper::parseShowOnConditions($field->showon, $field->formControl, $field->group)) . '\'';
		}

		if ($field->hidden)
		{
			echo $field->input;
		}
		else
		{
?>
			<div class="control-group<?php echo $groupClass; ?>"<?php echo $dataShowOn; ?>>
				<?php if ($name !== 'permissions') : ?>
					<div class="control-label">
						<?php echo $field->label; ?>
					</div>
				<?php endif; ?>
				<div class="<?php echo $name !== 'permissions' ? 'controls' : ''; ?>">
					<?php echo $field->input; ?>
				</div>
			</div>
<?php
		}
	}

	echo HTMLHelper::_($ui . '.endTab');
}

echo HTMLHelper::_($ui . '.endTabSet');
