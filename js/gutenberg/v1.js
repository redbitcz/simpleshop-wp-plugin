export default {
  attributes: {
    ssFormId: {
      type: 'string',
      default: __('Choose form', 'simpleshop-cz')
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