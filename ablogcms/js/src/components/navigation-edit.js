import React from 'react';
import Nestable from 'react-nestable';
import clone from 'clone';
import classNames from 'classnames';
import ReactTooltip from 'react-tooltip';
import NestableEdit from './nestable-edit';

/* eslint jsx-a11y/label-has-associated-control: 0 */

export default class NavigationEdit extends NestableEdit {
  constructor({ items }) {
    super();
    this.state = {
      isOpenAll: false,
    };
    this.onInitialize({ items });
  }

  defineDefaultItem() {
    return {
      navigation_label: '',
      navigation_uri: '',
      navigation_attr: '',
      navigation_a_attr: '',
      navigation_target: false,
      navigation_publish: true,
      toggle: false,
      hide: true,
    };
  }

  getRenderItem(item, collapseIcon, handler) {
    const { message } = this.props;
    return (
      <div
        className={classNames('acms-admin-form', {
          'acms-admin-nested-selected': item.selected,
          'acms-admin-nested-private': !item.navigation_publish,
        })}
        key={item}
      >
        <div className="acms-admin-nested-item">
          <div className="acms-admin-nested-item-header clearfix">
            <div className="acms-admin-nested-item-inner">
              {handler}
              <div className="acms-admin-form-checkbox">
                <label>
                  <input
                    type="checkbox"
                    checked={item.navigation_publish}
                    value="on"
                    onChange={(e) => {
                      e.stopPropagation();
                      this.updateItem(item.id, 'navigation_publish', e.target.checked);
                    }}
                  />
                  <i className="acms-admin-ico-checkbox" />
                  {message.open}
                </label>
              </div>
              <div className="acms-admin-nested-item-child">
                <div className="acms-admin-form-action">
                  <label
                    className="acms-admin-form-side"
                    style={{ whiteSpace: 'nowrap' }}
                    htmlFor={`navigation-item-label-${item.id}`}
                  >
                    {message.label}
                  </label>
                  <input
                    type="text"
                    className="acms-admin-nested-input"
                    id={`navigation-item-label-${item.id}`}
                    value={item.navigation_label}
                    onInput={(e) => {
                      this.updateItem(item.id, 'navigation_label', e.target.value);
                    }}
                  />
                </div>
              </div>
              <div className="acms-admin-nested-item-child acms-admin-nested-item-child-link">
                <div className="acms-admin-form-action">
                  <label
                    className="acms-admin-form-side"
                    style={{ whiteSpace: 'nowrap' }}
                    htmlFor={`navigation-item-url-${item.id}`}
                  >
                    URL
                  </label>
                  <input
                    type="text"
                    placeholder="https://example.com/"
                    className="acms-admin-nested-input"
                    id={`navigation-item-url-${item.id}`}
                    value={item.navigation_uri}
                    onInput={(e) => {
                      this.updateItem(item.id, 'navigation_uri', e.target.value);
                    }}
                  />
                </div>
              </div>
              {collapseIcon ? (
                <div>
                  <button
                    data-tip
                    data-for={`navigation-child-${item.id}`}
                    type="button"
                    className="acms-admin-btn-admin acms-admin-nested-collapse-btn"
                    onClick={this.toggleItem.bind(this, item)}
                  >
                    {collapseIcon}
                  </button>
                  <ReactTooltip id={`navigation-child-${item.id}`} place="top" type="dark" effect="solid">
                    <span>{ACMS.i18n('navigation.show_child_items')}</span>
                  </ReactTooltip>
                </div>
              ) : (
                <button type="button" className="acms-admin-btn-admin acms-admin-nested-disabled-btn" disabled>
                  ×
                </button>
              )}
              <button
                type="button"
                data-tip
                data-for={`navigation-item-detail-${item.id}`}
                className="acms-admin-btn-admin"
                onClick={this.updateItem.bind(this, item.id, 'toggle', !item.toggle, false)}
              >
                {message.detail}
              </button>
              <ReactTooltip id={`navigation-item-detail-${item.id}`} place="top" type="dark" effect="solid">
                <span>{ACMS.i18n('navigation.show_details')}</span>
              </ReactTooltip>
              <button
                type="button"
                data-tip
                data-for={`add-navigation-item-${item.id}`}
                onClick={this.addChild.bind(this, item)}
                className="acms-admin-btn-admin"
              >
                {message.add}
              </button>
              <ReactTooltip id={`add-navigation-item-${item.id}`} place="top" type="dark" effect="solid">
                <span>{ACMS.i18n('navigation.add_new_item')}</span>
              </ReactTooltip>
            </div>
          </div>
          {item.toggle && (
            <div className="acms-admin-nested-item-detail">
              <div className="acms-admin-nested-item-inner">
                <div className="acms-admin-form-checkbox">
                  <label>
                    <input
                      type="checkbox"
                      checked={item.navigation_target}
                      value="_blank"
                      onChange={(e) => {
                        this.updateItem(item.id, 'navigation_target', e.target.checked);
                      }}
                    />
                    <i className="acms-admin-ico-checkbox" />
                    _blank
                  </label>
                </div>
                <div className="acms-admin-nested-item-child">
                  <div className="acms-admin-form-action">
                    <label
                      className="acms-admin-form-side"
                      htmlFor={`navigation-item-attr-${item.id}`}
                      style={{ whiteSpace: 'nowrap' }}
                    >
                      {message.attr}
                    </label>
                    <input
                      type="text"
                      className="acms-admin-nested-input"
                      id={`navigation-item-attr-${item.id}`}
                      value={item.navigation_attr}
                      onInput={(e) => {
                        this.updateItem(item.id, 'navigation_attr', e.target.value);
                      }}
                    />
                  </div>
                </div>
                <div className="acms-admin-nested-item-child">
                  <div className="acms-admin-form-action">
                    <label
                      className="acms-admin-form-side"
                      htmlFor={`navigation-item-child_attr-${item.id}`}
                      style={{ whiteSpace: 'nowrap' }}
                    >
                      {message.child_attr}
                    </label>
                    <input
                      type="text"
                      className="acms-admin-nested-input"
                      id={`navigation-item-child_attr-${item.id}`}
                      value={item.navigation_a_attr}
                      onInput={(e) => {
                        this.updateItem(item.id, 'navigation_a_attr', e.target.value);
                      }}
                    />
                  </div>
                </div>
                <div className="acms-admin-nested-item-input">
                  <button
                    type="button"
                    className="acms-admin-btn acms-admin-btn-danger"
                    onClick={this.removeItem.bind(this, item.id)}
                  >
                    {message.remove}
                  </button>
                </div>
              </div>
            </div>
          )}
        </div>
      </div>
    );
  }

  openAll() {
    const { items } = this.state;
    items.forEach((item) => {
      item.toggle = true;
    });
    this.setState({
      isOpenAll: true,
      items,
    });
  }

  closeAll() {
    const { items } = this.state;
    items.forEach((item) => {
      item.toggle = false;
    });
    this.setState({
      isOpenAll: false,
    });
  }

  render() {
    const { isOpenAll } = this.state;
    const { items } = this.state;
    const nested = this.getNested(items);
    const reIndexed = this.reIndex(clone(items));
    const { preventAnimation } = this.state;
    return (
      <div className={classNames({ 'acms-admin-nested-prevent-animation': preventAnimation })}>
        <div className="clearfix" style={{ paddingBottom: '10px' }}>
          {isOpenAll ? (
            <button type="button" className="acms-admin-btn acms-admin-float-right" onClick={this.closeAll.bind(this)}>
              {ACMS.i18n('navigation.hide_all_details')}
            </button>
          ) : (
            <button type="button" className="acms-admin-btn acms-admin-float-right" onClick={this.openAll.bind(this)}>
              {ACMS.i18n('navigation.show_all_details')}
            </button>
          )}
        </div>
        <Nestable
          items={nested}
          collapsed={false}
          renderItem={({ item, collapseIcon, handler }) => this.getRenderItem(item, collapseIcon, handler)}
          onChange={this.onChange.bind(this)}
          ref={(ref) => {
            this.nestable = ref;
          }}
          handler={(
            <div className="acms-admin-nested-item-handle">
              <i className="acms-admin-icon-sort" />
            </div>
          )}
        />
        {reIndexed.map((item) => (
          <div>
            <input type="hidden" name="navigation_parent[]" value={item.parent ? item.parent : ''} />
            <input type="hidden" name="navigation_label[]" value={item.navigation_label ? item.navigation_label : ''} />
            <input type="hidden" name="navigation_attr[]" value={item.navigation_attr ? item.navigation_attr : ''} />
            <input
              type="hidden"
              name="navigation_a_attr[]"
              value={item.navigation_a_attr ? item.navigation_a_attr : ''}
            />
            <input type="hidden" name="navigation_uri[]" value={item.navigation_uri ? item.navigation_uri : ''} />
            <input type="hidden" name="navigation_publish[]" value={item.navigation_publish ? 'on' : 'off'} />
            <input type="hidden" name="navigation_target[]" value={item.navigation_target ? '_blank' : ''} />
          </div>
        ))}
        <input type="hidden" name="config[]" value="navigation_publish" />
        <input type="hidden" name="config[]" value="navigation_target" />
        <input type="hidden" name="config[]" value="navigation@sort" />
        <input type="hidden" name="config[]" value="navigation_label" />
        <input type="hidden" name="config[]" value="navigation_uri" />
        <input type="hidden" name="config[]" value="navigation_attr" />
        <input type="hidden" name="config[]" value="navigation_a_attr" />
        <input type="hidden" name="config[]" value="navigation_parent" />
      </div>
    );
  }
}
