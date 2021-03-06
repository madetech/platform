/* global define */
define(['jquery', 'underscore'],
function($, _) {
    'use strict';

    /**
     * @export oro/select2-config
     */
    var Select2Config = function (config, url, perPage) {
        this.config = config;
        this.url = url;
        this.perPage = perPage;
    };

    Select2Config.prototype = {
        getConfig: function () {
            var self = this;
            if (this.config.formatResult === undefined) {
                this.config.formatResult = this.format(this.config.result_template || false);
            }
            if (this.config.formatSelection === undefined) {
                this.config.formatSelection = this.format(this.config.selection_template || false);
            }
            if (this.config.initSelection === undefined) {
                this.config.initSelection = _.bind(this.initSelection, this);
            }
            if (this.config.ajax === undefined) {
                this.config.ajax = {
                    'url': this.url,
                    'data': function (query, page) {
                        return {
                            'page': page,
                            'per_page': self.perPage,
                            'query': query
                        };
                    },
                    'results': function (data, page) {
                        return data;
                    }
                };
            }
            if (this.config.ajax.quietMillis === undefined) {
                this.config.ajax.quietMillis = 700;
            }
            if (this.config.escapeMarkup === undefined) {
                this.config.escapeMarkup = function (m) { return m; };
            }
            return this.config;
        },

        format: function(jsTemplate) {
            var self = this;
            return function (object, container, query) {
                if ($.isEmptyObject(object)) {
                    return undefined;
                }
                var result = '', tpl,
                    highlight = function (str) {
                    return self.highlightSelection(str, query);
                };
                if (object._html !== undefined) {
                    result = object._html;
                } else if (jsTemplate) {
                    object.highlight = highlight;
                    tpl = _.template(jsTemplate);
                    result = tpl(object);
                } else {
                    result = highlight(self.getTitle(object, self.config.properties));
                }
                return result;
            };
        },

        initSelection: function(element, callback) {
            if (this.config.multiple === true) {
                callback(element.data('entities'));
            } else {
                callback(element.data('entities').pop());
            }
        },

        highlightSelection: function(str, selection) {
            return str && selection && selection.term ?
                str.replace(new RegExp(selection.term, 'ig'), '<span class="select2-match">$&</span>') : str;
        },

        getTitle: function(data, properties) {
            var title = '', result, i;
            if (data) {
                result = [];
                _.each(properties, function(property) {
                    result.push(data[property]);
                });
                title = result.join(' ');
            }
            return title;
        }
    };

    return Select2Config;
});
