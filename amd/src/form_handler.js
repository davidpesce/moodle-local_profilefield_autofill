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
 * Dynamic form handler for profile field mapping form
 *
 * @module     local_profilefield_autofill/form_handler
 * @copyright  2025 David Pesce
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery'], function($) {

    var FormHandler = function() {
        this.fieldTypes = {};
        this.currentTargetField = '';
        this.currentTargetValue = '';
        this.currentSourceField = '';
        this.currentSourceValue = '';
        this.targetFieldSelect = null;
        this.targetValueContainer = null;
        this.sourceFieldSelect = null;
        this.sourceValueContainer = null;
    };

    FormHandler.prototype.init = function(fieldTypes, currentTargetField, currentTargetValue, currentSourceField,
            currentSourceValue) {
        var self = this;

        self.fieldTypes = fieldTypes || {};
        self.currentTargetField = currentTargetField || '';
        self.currentTargetValue = currentTargetValue || '';
        self.currentSourceField = currentSourceField || '';
        self.currentSourceValue = currentSourceValue || '';

        // Wait for DOM to be ready
        $(document).ready(function() {
            self.setupForm();
        });
    };

    FormHandler.prototype.setupForm = function() {
        var self = this;

        self.targetFieldSelect = document.getElementById('id_targetfield');
        self.targetValueContainer = document.getElementById('fitem_id_targetvalue');
        self.sourceFieldSelect = document.getElementById('id_sourcefield');
        self.sourceValueContainer = document.getElementById('fitem_id_sourcevalue');

        if (!self.targetFieldSelect || !self.targetValueContainer || !self.sourceFieldSelect || !self.sourceValueContainer) {
            // Elements might not be available yet, try again after a short delay
            setTimeout(function() {
                self.setupForm();
            }, 500);
            return;
        }

        // Bind change events
        $(self.targetFieldSelect).on('change', function() {
            self.updateTargetField();
        });

        $(self.sourceFieldSelect).on('change', function() {
            self.updateSourceField();
        });

        // Initialize the form
        self.updateTargetField();
        self.updateSourceField();
    };

    FormHandler.prototype.updateTargetField = function() {
        var self = this;
        var selectedField = self.targetFieldSelect.value;
        var fieldInfo = self.fieldTypes[selectedField];

        if (!fieldInfo) {
            self.resetToTextInput();
            return;
        }

        if (fieldInfo.type === 'menu' && fieldInfo.options) {
            self.updateToDropdown(fieldInfo.options);
        } else if (fieldInfo.type === 'checkbox') {
            self.updateToCheckbox();
        } else {
            self.resetToTextInput();
        }
    };

    FormHandler.prototype.resetToTextInput = function() {
        var self = this;
        var container = self.targetValueContainer.querySelector('.felement');

        if (container) {
            var inputValue = (self.targetFieldSelect.value === self.currentTargetField) ? self.currentTargetValue : '';

            // Create input element properly
            var input = document.createElement('input');
            input.type = 'text';
            input.name = 'targetvalue';
            input.id = 'id_targetvalue';
            input.size = 50;
            input.className = 'form-control';
            input.value = inputValue;

            container.innerHTML = '';
            container.appendChild(input);
        }
    };

    FormHandler.prototype.updateToDropdown = function(options) {
        var self = this;
        var container = self.targetValueContainer.querySelector('.felement');

        if (container && options) {
            var selectedValue = (self.targetFieldSelect.value === self.currentTargetField) ? self.currentTargetValue : '';

            // Create select element
            var select = document.createElement('select');
            select.name = 'targetvalue';
            select.id = 'id_targetvalue';
            select.className = 'form-control';

            // Add default option
            var defaultOption = document.createElement('option');
            defaultOption.value = '';
            defaultOption.textContent = 'Choose...';
            select.appendChild(defaultOption);

            // Add options from field configuration
            var optionList = options.toString().split('\n');
            for (var i = 0; i < optionList.length; i++) {
                var optionText = optionList[i].trim();
                if (optionText) {
                    var option = document.createElement('option');
                    option.value = optionText;
                    option.textContent = optionText;
                    if (optionText === selectedValue) {
                        option.selected = true;
                    }
                    select.appendChild(option);
                }
            }

            container.innerHTML = '';
            container.appendChild(select);
        }
    };

    FormHandler.prototype.updateToCheckbox = function() {
        var self = this;
        var container = self.targetValueContainer.querySelector('.felement');

        if (container) {
            var selectedValue = (self.targetFieldSelect.value === self.currentTargetField) ? self.currentTargetValue : '';

            var select = document.createElement('select');
            select.name = 'targetvalue';
            select.id = 'id_targetvalue';
            select.className = 'form-control';

            // Add options for Yes/No
            var options = [
                {value: '', text: 'Choose...'},
                {value: '1', text: 'Yes'},
                {value: '0', text: 'No'}
            ];

            for (var i = 0; i < options.length; i++) {
                var option = document.createElement('option');
                option.value = options[i].value;
                option.textContent = options[i].text;
                if (options[i].value === selectedValue) {
                    option.selected = true;
                }
                select.appendChild(option);
            }

            container.innerHTML = '';
            container.appendChild(select);
        }
    };

    FormHandler.prototype.updateSourceField = function() {
        var self = this;
        var selectedField = self.sourceFieldSelect.value;
        var fieldInfo = self.fieldTypes[selectedField];

        if (!fieldInfo) {
            self.resetSourceToTextInput();
            return;
        }

        if (fieldInfo.type === 'menu' && fieldInfo.options) {
            self.updateSourceToDropdown(fieldInfo.options);
        } else if (fieldInfo.type === 'checkbox') {
            self.updateSourceToCheckbox();
        } else {
            self.resetSourceToTextInput();
        }
    };

    FormHandler.prototype.resetSourceToTextInput = function() {
        var self = this;
        var container = self.sourceValueContainer.querySelector('.felement');

        if (container) {
            var inputValue = (self.sourceFieldSelect.value === self.currentSourceField) ? self.currentSourceValue : '';

            // Create input element properly
            var input = document.createElement('input');
            input.type = 'text';
            input.name = 'sourcevalue';
            input.id = 'id_sourcevalue';
            input.size = 50;
            input.className = 'form-control';
            input.value = inputValue;

            container.innerHTML = '';
            container.appendChild(input);
        }
    };

    FormHandler.prototype.updateSourceToDropdown = function(options) {
        var self = this;
        var container = self.sourceValueContainer.querySelector('.felement');

        if (container && options) {
            var selectedValue = (self.sourceFieldSelect.value === self.currentSourceField) ? self.currentSourceValue : '';

            // Create select element
            var select = document.createElement('select');
            select.name = 'sourcevalue';
            select.id = 'id_sourcevalue';
            select.className = 'form-control';

            // Add default option for wildcard
            var defaultOption = document.createElement('option');
            defaultOption.value = '';
            defaultOption.textContent = 'Choose or type custom value...';
            select.appendChild(defaultOption);

            // Add wildcard option
            var wildcardOption = document.createElement('option');
            wildcardOption.value = '*';
            wildcardOption.textContent = '* (Any value)';
            select.appendChild(wildcardOption);

            // Add options from field configuration
            var optionList = options.toString().split('\n');
            for (var i = 0; i < optionList.length; i++) {
                var optionText = optionList[i].trim();
                if (optionText) {
                    var option = document.createElement('option');
                    option.value = optionText;
                    option.textContent = optionText;
                    if (optionText === selectedValue) {
                        option.selected = true;
                    }
                    select.appendChild(option);
                }
            }

            // If current value doesn't match any option, select the default
            if (selectedValue && selectedValue !== '*') {
                var foundMatch = false;
                for (var j = 0; j < optionList.length; j++) {
                    if (optionList[j].trim() === selectedValue) {
                        foundMatch = true;
                        break;
                    }
                }
                if (!foundMatch) {
                    // Keep as text input for custom values
                    self.resetSourceToTextInput();
                    return;
                }
            }

            container.innerHTML = '';
            container.appendChild(select);
        }
    };

    FormHandler.prototype.updateSourceToCheckbox = function() {
        var self = this;
        var container = self.sourceValueContainer.querySelector('.felement');

        if (container) {
            var selectedValue = (self.sourceFieldSelect.value === self.currentSourceField) ? self.currentSourceValue : '';

            var select = document.createElement('select');
            select.name = 'sourcevalue';
            select.id = 'id_sourcevalue';
            select.className = 'form-control';

            // Add options for checkbox values
            var options = [
                {value: '', text: 'Choose...'},
                {value: '*', text: '* (Any value)'},
                {value: '1', text: 'Yes'},
                {value: '0', text: 'No'}
            ];

            for (var i = 0; i < options.length; i++) {
                var option = document.createElement('option');
                option.value = options[i].value;
                option.textContent = options[i].text;
                if (options[i].value === selectedValue) {
                    option.selected = true;
                }
                select.appendChild(option);
            }

            container.innerHTML = '';
            container.appendChild(select);
        }
    };

    return {
        init: function(fieldTypes, currentTargetField, currentTargetValue, currentSourceField, currentSourceValue) {
            var handler = new FormHandler();
            handler.init(fieldTypes, currentTargetField, currentTargetValue, currentSourceField, currentSourceValue);
        }
    };
});