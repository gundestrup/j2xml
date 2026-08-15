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

$relevantFields = [
	'content'    => ['export_compression', 'export_categories', 'export_fields', 'export_images', 'export_tags'],
	'categories' => ['export_compression', 'export_users', 'export_images', 'export_tags'],
	'users'      => ['export_compression', 'export_password', 'export_usernotes', 'export_contacts', 'export_fields'],
	'contact'    => ['export_compression', 'export_users', 'export_images', 'export_tags', 'export_categories'],
	'fields'     => ['export_compression', 'export_users', 'export_categories'],
	'menus'      => ['export_compression', 'export_categories', 'export_fields', 'export_images', 'export_tags'],
	'modules'    => ['export_compression'],
	'viewlevels' => ['export_compression'],
	'usernotes'  => ['export_compression', 'export_users', 'export_images', 'export_categories'],
	'weblinks'   => ['export_compression', 'export_users', 'export_images', 'export_tags', 'export_categories'],
];
$allowedFields = $relevantFields[$layout] ?? null;
$fieldKey = static function ($field): string {
	if (preg_match('/\\[([^\\]]+)\\]$/', $field->name, $matches))
	{
		return $matches[1];
	}

	return $field->name;
};
$fieldInputName = static fn($field): string => str_contains($field->name, '[') ? $field->name : 'jform[' . $field->name . ']';

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

	$fields = $form->getFieldset($name);
	$visibleFields = array_filter($fields, static fn($field) => !$field->hidden && ($allowedFields === null || in_array($fieldKey($field), $allowedFields, true)));
	if (!$visibleFields)
	{
		foreach ($fields as $field)
		{
			if ($field->hidden || ($allowedFields !== null && !in_array($fieldKey($field), $allowedFields, true)))
			{
				echo $fieldKey($field) === 'cid' ? $field->input : '<input type="hidden" name="' . htmlspecialchars($fieldInputName($field), ENT_QUOTES, 'UTF-8') . '" value="0">';
			}
		}
		continue;
	}

	$label = empty($fieldSet->label) ? 'COM_J2XML_' . $name . '_FIELDSET_LABEL' : $fieldSet->label;
	echo HTMLHelper::_($ui . '.addTab', $tabSetId, $name, Text::_($label));

	foreach ($fields as $field)
	{
		$dataShowOn = '';
		$groupClass = $field->type === 'Spacer' ? ' field-spacer' : '';

		if ($field->showon)
		{
			$dataShowOn = ' data-showon=\'' . json_encode(FormHelper::parseShowOnConditions($field->showon, $field->formControl, $field->group)) . '\'';
		}

		if ($field->hidden || ($allowedFields !== null && !in_array($fieldKey($field), $allowedFields, true)))
		{
			echo $fieldKey($field) === 'cid' ? $field->input : '<input type="hidden" name="' . htmlspecialchars($fieldInputName($field), ENT_QUOTES, 'UTF-8') . '" value="0">';
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
