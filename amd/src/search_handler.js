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
 * Search functionality for profile field mapping management
 *
 * @module     local_profilefield_autofill/search_handler
 * @copyright  2025 David Pesce
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery'], function($) {

    var SearchHandler = function() {
        this.searchInput = null;
        this.searchTimeout = null;
    };

    SearchHandler.prototype.init = function() {
        var self = this;

        // Wait for DOM to be ready
        $(document).ready(function() {
            self.setupSearch();
        });
    };

    SearchHandler.prototype.setupSearch = function() {
        var self = this;

        self.searchInput = document.getElementById('mapping-search');

        if (!self.searchInput) {
            // Search input might not be available, try again after a short delay
            setTimeout(function() {
                self.setupSearch();
            }, 500);
            return;
        }

        // Bind events
        $(self.searchInput).on('input keyup', function() {
            self.debouncedSearch();
        });
    };

    SearchHandler.prototype.performSearch = function() {
        var self = this;
        var searchTerm = self.searchInput.value.trim().toLowerCase();

        // Get current view elements
        var table = document.getElementById('profilefield-mappings-table');
        var container = document.getElementById('grouped-mappings-container');

        if (searchTerm === '') {
            // Show all elements
            self.showAllElements(table, container);
            return;
        }

        // Search table view
        self.searchTableView(table, searchTerm);

        // Search grouped view
        self.searchGroupedView(container, searchTerm);
    };

    SearchHandler.prototype.showAllElements = function(table, container) {
        var i;
        if (table) {
            var rows = table.querySelectorAll('tbody tr');
            for (i = 0; i < rows.length; i++) {
                rows[i].style.display = '';
            }
        }
        if (container) {
            var cards = container.querySelectorAll('.card');
            for (i = 0; i < cards.length; i++) {
                cards[i].style.display = '';
            }
        }
    };

    SearchHandler.prototype.searchTableView = function(table, searchTerm) {
        if (table) {
            var tbody = table.querySelector('tbody');
            if (tbody) {
                var rows = tbody.querySelectorAll('tr');
                for (var i = 0; i < rows.length; i++) {
                    var row = rows[i];
                    var text = row.textContent.toLowerCase();
                    row.style.display = text.indexOf(searchTerm) !== -1 ? '' : 'none';
                }
            }
        }
    };

    SearchHandler.prototype.searchGroupedView = function(container, searchTerm) {
        if (container) {
            var cards = container.querySelectorAll('.card');
            for (var i = 0; i < cards.length; i++) {
                var card = cards[i];
                var text = card.textContent.toLowerCase();
                card.style.display = text.indexOf(searchTerm) !== -1 ? '' : 'none';
            }
        }
    };

    SearchHandler.prototype.debouncedSearch = function() {
        var self = this;
        clearTimeout(self.searchTimeout);
        self.searchTimeout = setTimeout(function() {
            self.performSearch();
        }, 300);
    };

    return {
        init: function() {
            var handler = new SearchHandler();
            handler.init();
        }
    };
});