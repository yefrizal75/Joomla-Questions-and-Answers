<?php
/*--------------------------------------------------------------------------------------------------------|  www.vdm.io  |------/
    __      __       _     _____                 _                                  _     __  __      _   _               _
    \ \    / /      | |   |  __ \               | |                                | |   |  \/  |    | | | |             | |
     \ \  / /_ _ ___| |_  | |  | | _____   _____| | ___  _ __  _ __ ___   ___ _ __ | |_  | \  / | ___| |_| |__   ___   __| |
      \ \/ / _` / __| __| | |  | |/ _ \ \ / / _ \ |/ _ \| '_ \| '_ ` _ \ / _ \ '_ \| __| | |\/| |/ _ \ __| '_ \ / _ \ / _` |
       \  / (_| \__ \ |_  | |__| |  __/\ V /  __/ | (_) | |_) | | | | | |  __/ | | | |_  | |  | |  __/ |_| | | | (_) | (_| |
        \/ \__,_|___/\__| |_____/ \___| \_/ \___|_|\___/| .__/|_| |_| |_|\___|_| |_|\__| |_|  |_|\___|\__|_| |_|\___/ \__,_|
                                                        | |
                                                        |_|
/-------------------------------------------------------------------------------------------------------------------------------/

	@version		1.1.x
	@build			27th May, 2022
	@created		30th January, 2017
	@package		Questions and Answers
	@subpackage		view.html.php
	@author			Llewellyn van der Merwe <https://www.vdm.io/>
	@copyright		Copyright (C) 2015. All Rights Reserved
	@license		GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html

	Questions &amp; Answers

/-----------------------------------------------------------------------------------------------------------------------------*/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\MVC\View\HtmlView;

/**
 * Questionsanswers Html View class for the Help_documents
 */
class QuestionsanswersViewHelp_documents extends HtmlView
{
	/**
	 * Help_documents view display method
	 * @return void
	 */
	function display($tpl = null)
	{
		if ($this->getLayout() !== 'modal')
		{
			// Include helper submenu
			QuestionsanswersHelper::addSubmenu('help_documents');
		}

		// Assign data to the view
		$this->items = $this->get('Items');
		$this->pagination = $this->get('Pagination');
		$this->state = $this->get('State');
		$this->user = JFactory::getUser();
		// Load the filter form from xml.
		$this->filterForm = $this->get('FilterForm');
		// Load the active filters.
		$this->activeFilters = $this->get('ActiveFilters');
		// Add the list ordering clause.
		$this->listOrder = $this->escape($this->state->get('list.ordering', 'a.id'));
		$this->listDirn = $this->escape($this->state->get('list.direction', 'DESC'));
		$this->saveOrder = $this->listOrder == 'a.ordering';
		// set the return here value
		$this->return_here = urlencode(base64_encode((string) JUri::getInstance()));
		// get global action permissions
		$this->canDo = QuestionsanswersHelper::getActions('help_document');
		$this->canEdit = $this->canDo->get('help_document.edit');
		$this->canState = $this->canDo->get('help_document.edit.state');
		$this->canCreate = $this->canDo->get('help_document.create');
		$this->canDelete = $this->canDo->get('help_document.delete');
		$this->canBatch = $this->canDo->get('core.batch');

		// We don't need toolbar in the modal window.
		if ($this->getLayout() !== 'modal')
		{
			$this->addToolbar();
			$this->sidebar = JHtmlSidebar::render();
			// load the batch html
			if ($this->canCreate && $this->canEdit && $this->canState)
			{
				$this->batchDisplay = JHtmlBatch_::render();
			}
		}
		
		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			throw new Exception(implode("\n", $errors), 500);
		}

		// Display the template
		parent::display($tpl);

		// Set the document
		$this->setDocument();
	}

	/**
	 * Setting the toolbar
	 */
	protected function addToolBar()
	{
		JToolBarHelper::title(JText::_('COM_QUESTIONSANSWERS_HELP_DOCUMENTS'), 'support');
		JHtmlSidebar::setAction('index.php?option=com_questionsanswers&view=help_documents');
		JFormHelper::addFieldPath(JPATH_COMPONENT . '/models/fields');

		if ($this->canCreate)
		{
			JToolBarHelper::addNew('help_document.add');
		}

		// Only load if there are items
		if (QuestionsanswersHelper::checkArray($this->items))
		{
			if ($this->canEdit)
			{
				JToolBarHelper::editList('help_document.edit');
			}

			if ($this->canState)
			{
				JToolBarHelper::publishList('help_documents.publish');
				JToolBarHelper::unpublishList('help_documents.unpublish');
				JToolBarHelper::archiveList('help_documents.archive');

				if ($this->canDo->get('core.admin'))
				{
					JToolBarHelper::checkin('help_documents.checkin');
				}
			}

			// Add a batch button
			if ($this->canBatch && $this->canCreate && $this->canEdit && $this->canState)
			{
				// Get the toolbar object instance
				$bar = JToolBar::getInstance('toolbar');
				// set the batch button name
				$title = JText::_('JTOOLBAR_BATCH');
				// Instantiate a new JLayoutFile instance and render the batch button
				$layout = new JLayoutFile('joomla.toolbar.batch');
				// add the button to the page
				$dhtml = $layout->render(array('title' => $title));
				$bar->appendButton('Custom', $dhtml, 'batch');
			}

			if ($this->state->get('filter.published') == -2 && ($this->canState && $this->canDelete))
			{
				JToolbarHelper::deleteList('', 'help_documents.delete', 'JTOOLBAR_EMPTY_TRASH');
			}
			elseif ($this->canState && $this->canDelete)
			{
				JToolbarHelper::trash('help_documents.trash');
			}

			if ($this->canDo->get('core.export') && $this->canDo->get('help_document.export'))
			{
				JToolBarHelper::custom('help_documents.exportData', 'download', '', 'COM_QUESTIONSANSWERS_EXPORT_DATA', true);
			}
		}

		if ($this->canDo->get('core.import') && $this->canDo->get('help_document.import'))
		{
			JToolBarHelper::custom('help_documents.importData', 'upload', '', 'COM_QUESTIONSANSWERS_IMPORT_DATA', false);
		}

		// set help url for this view if found
		$this->help_url = QuestionsanswersHelper::getHelpUrl('help_documents');
		if (QuestionsanswersHelper::checkString($this->help_url))
		{
				JToolbarHelper::help('COM_QUESTIONSANSWERS_HELP_MANAGER', false, $this->help_url);
		}

		// add the options comp button
		if ($this->canDo->get('core.admin') || $this->canDo->get('core.options'))
		{
			JToolBarHelper::preferences('com_questionsanswers');
		}

		// Only load published batch if state and batch is allowed
		if ($this->canState && $this->canBatch)
		{
			JHtmlBatch_::addListSelection(
				JText::_('COM_QUESTIONSANSWERS_KEEP_ORIGINAL_STATE'),
				'batch[published]',
				JHtml::_('select.options', JHtml::_('jgrid.publishedOptions', array('all' => false)), 'value', 'text', '', true)
			);
		}

		// Only load Type batch if create, edit, and batch is allowed
		if ($this->canBatch && $this->canCreate && $this->canEdit)
		{
			// Set Type Selection
			$this->typeOptions = JFormHelper::loadFieldType('helpdocumentsfiltertype')->options;
			// We do some sanitation for Type filter
			if (QuestionsanswersHelper::checkArray($this->typeOptions) &&
				isset($this->typeOptions[0]->value) &&
				!QuestionsanswersHelper::checkString($this->typeOptions[0]->value))
			{
				unset($this->typeOptions[0]);
			}
			// Type Batch Selection
			JHtmlBatch_::addListSelection(
				'- Keep Original '.JText::_('COM_QUESTIONSANSWERS_HELP_DOCUMENT_TYPE_LABEL').' -',
				'batch[type]',
				JHtml::_('select.options', $this->typeOptions, 'value', 'text')
			);
		}

		// Only load Location batch if create, edit, and batch is allowed
		if ($this->canBatch && $this->canCreate && $this->canEdit)
		{
			// Set Location Selection
			$this->locationOptions = JFormHelper::loadFieldType('helpdocumentsfilterlocation')->options;
			// We do some sanitation for Location filter
			if (QuestionsanswersHelper::checkArray($this->locationOptions) &&
				isset($this->locationOptions[0]->value) &&
				!QuestionsanswersHelper::checkString($this->locationOptions[0]->value))
			{
				unset($this->locationOptions[0]);
			}
			// Location Batch Selection
			JHtmlBatch_::addListSelection(
				'- Keep Original '.JText::_('COM_QUESTIONSANSWERS_HELP_DOCUMENT_LOCATION_LABEL').' -',
				'batch[location]',
				JHtml::_('select.options', $this->locationOptions, 'value', 'text')
			);
		}

		// Only load Admin View batch if create, edit, and batch is allowed
		if ($this->canBatch && $this->canCreate && $this->canEdit)
		{
			// Set Admin View Selection
			$this->admin_viewOptions = JFormHelper::loadFieldType('Adminviewfolderlist')->options;
			// We do some sanitation for Admin View filter
			if (QuestionsanswersHelper::checkArray($this->admin_viewOptions) &&
				isset($this->admin_viewOptions[0]->value) &&
				!QuestionsanswersHelper::checkString($this->admin_viewOptions[0]->value))
			{
				unset($this->admin_viewOptions[0]);
			}
			// Admin View Batch Selection
			JHtmlBatch_::addListSelection(
				'- Keep Original '.JText::_('COM_QUESTIONSANSWERS_HELP_DOCUMENT_ADMIN_VIEW_LABEL').' -',
				'batch[admin_view]',
				JHtml::_('select.options', $this->admin_viewOptions, 'value', 'text')
			);
		}

		// Only load Site View batch if create, edit, and batch is allowed
		if ($this->canBatch && $this->canCreate && $this->canEdit)
		{
			// Set Site View Selection
			$this->site_viewOptions = JFormHelper::loadFieldType('Siteviewfolderlist')->options;
			// We do some sanitation for Site View filter
			if (QuestionsanswersHelper::checkArray($this->site_viewOptions) &&
				isset($this->site_viewOptions[0]->value) &&
				!QuestionsanswersHelper::checkString($this->site_viewOptions[0]->value))
			{
				unset($this->site_viewOptions[0]);
			}
			// Site View Batch Selection
			JHtmlBatch_::addListSelection(
				'- Keep Original '.JText::_('COM_QUESTIONSANSWERS_HELP_DOCUMENT_SITE_VIEW_LABEL').' -',
				'batch[site_view]',
				JHtml::_('select.options', $this->site_viewOptions, 'value', 'text')
			);
		}
	}

	/**
	 * Method to set up the document properties
	 *
	 * @return void
	 */
	protected function setDocument()
	{
		if (!isset($this->document))
		{
			$this->document = JFactory::getDocument();
		}
		$this->document->setTitle(JText::_('COM_QUESTIONSANSWERS_HELP_DOCUMENTS'));
		$this->document->addStyleSheet(JURI::root() . "administrator/components/com_questionsanswers/assets/css/help_documents.css", (QuestionsanswersHelper::jVersion()->isCompatible('3.8.0')) ? array('version' => 'auto') : 'text/css');
	}

	/**
	 * Escapes a value for output in a view script.
	 *
	 * @param   mixed  $var  The output to escape.
	 *
	 * @return  mixed  The escaped value.
	 */
	public function escape($var)
	{
		if(strlen($var) > 50)
		{
			// use the helper htmlEscape method instead and shorten the string
			return QuestionsanswersHelper::htmlEscape($var, $this->_charset, true);
		}
		// use the helper htmlEscape method instead.
		return QuestionsanswersHelper::htmlEscape($var, $this->_charset);
	}

	/**
	 * Returns an array of fields the table can be sorted by
	 *
	 * @return  array  Array containing the field name to sort by as the key and display text as value
	 */
	protected function getSortFields()
	{
		return array(
			'a.ordering' => JText::_('JGRID_HEADING_ORDERING'),
			'a.published' => JText::_('JSTATUS'),
			'a.title' => JText::_('COM_QUESTIONSANSWERS_HELP_DOCUMENT_TITLE_LABEL'),
			'a.type' => JText::_('COM_QUESTIONSANSWERS_HELP_DOCUMENT_TYPE_LABEL'),
			'a.location' => JText::_('COM_QUESTIONSANSWERS_HELP_DOCUMENT_LOCATION_LABEL'),
			'g.' => JText::_('COM_QUESTIONSANSWERS_HELP_DOCUMENT_ADMIN_VIEW_LABEL'),
			'h.' => JText::_('COM_QUESTIONSANSWERS_HELP_DOCUMENT_SITE_VIEW_LABEL'),
			'a.id' => JText::_('JGRID_HEADING_ID')
		);
	}
}
