import {__} from '@wordpress/i18n';

export default {
  attributes: {
    ssFormId: {
      type: 'string',
      default: '(' + __('Choose the Product at the right panel', 'simpleshop-cz') + ')'
    },
  },
  save: function ({ attributes, className }) {
    const formId = attributes.ssFormId;
    const scriptUrl = 'https://form.simpleshop.cz/iframe/js/?id=' + formId;

    return (
      <div class="simpleshop-form" className={className}>
        <script type="text/javascript" src={scriptUrl} />
      </div>
    );
  },
}