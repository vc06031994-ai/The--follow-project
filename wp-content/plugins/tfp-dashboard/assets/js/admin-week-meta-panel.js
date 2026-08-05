(function (wp) {
    'use strict';

    if (!wp || !wp.plugins || !wp.editPost) {
        return;
    }

    var registerPlugin = wp.plugins.registerPlugin;
    var PluginDocumentSettingPanel = wp.editPost.PluginDocumentSettingPanel;
    var TextControl = wp.components.TextControl;
    var TextareaControl = wp.components.TextareaControl;
    var withSelect = wp.data.withSelect;
    var withDispatch = wp.data.withDispatch;
    var compose = wp.compose.compose;
    var el = wp.element.createElement;
    var __ = wp.i18n.__;

    var FIELDS = [
        { key: 'tfp_week_video_url', label: __('Video URL', 'tfp-dashboard'), placeholder: 'https://...' },
        { key: 'tfp_week_meeting_date', label: __('Meeting Date', 'tfp-dashboard'), placeholder: 'e.g. June 12, 2026' },
        { key: 'tfp_week_meeting_time', label: __('Meeting Time', 'tfp-dashboard'), placeholder: 'e.g. 6:00 PM PT' },
        { key: 'tfp_week_facilitator_name', label: __('Facilitator Name', 'tfp-dashboard'), placeholder: 'e.g. Chris Soloc' },
    ];

    var TfpWeekPanel = compose(
        withSelect(function (select) {
            return {
                meta: select('core/editor').getEditedPostAttribute('meta') || {},
            };
        }),
        withDispatch(function (dispatch) {
            return {
                setMeta: function (meta) {
                    dispatch('core/editor').editPost({ meta: meta });
                },
            };
        })
    )(function (props) {
        var elements = FIELDS.map(function (field) {
            return el(TextControl, {
                key: field.key,
                label: field.label,
                placeholder: field.placeholder,
                value: props.meta[field.key] || '',
                onChange: function (value) {
                    var updated = {};
                    for (var k in props.meta) {
                        if (Object.prototype.hasOwnProperty.call(props.meta, k)) {
                            updated[k] = props.meta[k];
                        }
                    }
                    updated[field.key] = value;
                    props.setMeta(updated);
                },
            });
        });

        elements.push(
            el(TextareaControl, {
                key: 'tfp_week_homework_questions',
                label: __('Homework Questions (JSON)', 'tfp-dashboard'),
                help: __('Format: [{"id":"q1","type":"multiple_choice","prompt":"...","options":["A","B"],"correct_index":1},{"id":"q2","type":"both","prompt":"...","yes_no":true}]', 'tfp-dashboard'),
                value: props.meta['tfp_week_homework_questions'] || '',
                rows: 10,
                onChange: function (value) {
                    var updated = {};
                    for (var k in props.meta) {
                        if (Object.prototype.hasOwnProperty.call(props.meta, k)) {
                            updated[k] = props.meta[k];
                        }
                    }
                    updated['tfp_week_homework_questions'] = value;
                    props.setMeta(updated);
                },
            })
        );

        return el(
            PluginDocumentSettingPanel,
            { name: 'tfp-week-details', title: __('TFP Week Details', 'tfp-dashboard') },
            elements
        );
    });

    registerPlugin('tfp-week-details-panel', { render: TfpWeekPanel });
})(window.wp);
