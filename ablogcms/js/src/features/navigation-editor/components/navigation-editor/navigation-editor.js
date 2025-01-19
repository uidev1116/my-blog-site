import { Fragment } from 'react';
import Nestable from 'react-nestable';
import clone from 'clone';
import classNames from 'classnames';
import { Tooltip } from 'react-tooltip';
import NestableEdit from './nestable-edit';
import DraggableButton from '../../../../components/draggable-button/draggable-button';

import 'react-nestable/dist/styles/index.css';

/* eslint jsx-a11y/label-has-associated-control: 0 */

export default class NavigationEditor extends NestableEdit {
  constructor(props) {
    super(props);
    this.state = {
      isOpenAll: false,
    };
    this.onInitialize({ items: props.items });
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
          'acms-admin-nested-private': !item.navigation_publish,
        })}
        key={item.uuid}
      >
        <div className="acms-admin-nested-item">
          <div className="acms-admin-nested-item-header clearfix">
            <div className="acms-admin-nested-item-inner">
              <div className="acms-admin-nested-item-handle">{handler}</div>
              <div className="acms-admin-nested-item-child acms-admin-nested-item-child-checkbox">
                <div className="acms-admin-form-checkbox">
                  <label>
                    <input
                      id={`navigation-item-publish-${item.uuid}`}
                      type="checkbox"
                      defaultChecked={item.navigation_publish}
                      value="on"
                      onChange={(event) => {
                        event.stopPropagation();
                        this.updateItem(item.id, 'navigation_publish', event.target.checked);
                      }}
                    />
                    <i className="acms-admin-ico-checkbox" />
                    {message.open}
                  </label>
                </div>
              </div>
              <div className="acms-admin-nested-item-child">
                <div className="acms-admin-form-action">
                  <label
                    className="acms-admin-form-side"
                    style={{ whiteSpace: 'nowrap' }}
                    htmlFor={`navigation-item-label-${item.uuid}`}
                  >
                    {message.label}
                  </label>
                  <input
                    type="text"
                    className="acms-admin-form-width-full"
                    id={`navigation-item-label-${item.uuid}`}
                    defaultValue={item.navigation_label}
                    onChange={(event) => {
                      this.updateItem(item.id, 'navigation_label', event.target.value);
                    }}
                  />
                </div>
              </div>
              <div className="acms-admin-nested-item-child acms-admin-nested-item-child-link">
                <div className="acms-admin-form-action">
                  <label
                    className="acms-admin-form-side"
                    style={{ whiteSpace: 'nowrap' }}
                    htmlFor={`navigation-item-url-${item.uuid}`}
                  >
                    URL
                  </label>
                  <input
                    type="text"
                    placeholder="https://example.com/"
                    className="acms-admin-nested-input"
                    id={`navigation-item-url-${item.uuid}`}
                    defaultValue={item.navigation_uri}
                    onChange={(event) => {
                      this.updateItem(item.id, 'navigation_uri', event.target.value);
                    }}
                  />
                </div>
              </div>
              <div className="acms-admin-nested-item-actions">
                {collapseIcon && (
                  <>
                    <button
                      data-tooltip-id={`navigation-child-${item.uuid}`}
                      data-tooltip-variant="dark"
                      data-tooltip-place="top"
                      data-tooltip-content={ACMS.i18n('navigation.show_child_items')}
                      type="button"
                      className="acms-admin-btn-admin acms-admin-nested-collapse-btn"
                    >
                      {collapseIcon}
                    </button>
                    <Tooltip id={`navigation-child-${item.uuid}`} />
                  </>
                )}
                <button
                  type="button"
                  data-tooltip-id={`navigation-item-detail-${item.uuid}`}
                  data-tooltip-content={ACMS.i18n('navigation.show_details')}
                  data-tooltip-variant="dark"
                  data-tooltip-place="top"
                  className="acms-admin-btn-admin"
                  onClick={this.updateItem.bind(this, item.id, 'toggle', !item.toggle, false)}
                >
                  {message.detail}
                </button>
                <Tooltip id={`navigation-item-detail-${item.uuid}`} />
                <button
                  type="button"
                  data-tooltip-id={`add-navigation-item-${item.uuid}`}
                  data-tooltip-content={ACMS.i18n('navigation.add_new_item')}
                  data-tooltip-variant="dark"
                  data-tooltip-place="top"
                  onClick={this.addChild.bind(this, item)}
                  className="acms-admin-btn-admin"
                >
                  {message.add}
                </button>
                <Tooltip id={`add-navigation-item-${item.uuid}`} />
              </div>
            </div>
          </div>
          {item.toggle && (
            <div className="acms-admin-nested-item-detail">
              <div className="acms-admin-nested-item-inner">
                <div className="acms-admin-nested-item-child acms-admin-nested-item-child-checkbox">
                  <div className="acms-admin-form-checkbox">
                    <label>
                      <input
                        id={`navigation-item-target-${item.id}`}
                        type="checkbox"
                        defaultChecked={item.navigation_target}
                        value="_blank"
                        onChange={(event) => {
                          event.stopPropagation();
                          this.updateItem(item.id, 'navigation_target', event.target.checked);
                        }}
                      />
                      <i className="acms-admin-ico-checkbox" />
                      _blank
                    </label>
                  </div>
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
                      defaultValue={item.navigation_attr}
                      onChange={(event) => {
                        this.updateItem(item.id, 'navigation_attr', event.target.value);
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
                      defaultValue={item.navigation_a_attr}
                      onChange={(event) => {
                        this.updateItem(item.id, 'navigation_a_attr', event.target.value);
                      }}
                    />
                  </div>
                </div>
                <div className="acms-admin-nested-item-actions">
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
    return (
      <div>
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
          className="acms-admin-nested-items"
          items={nested}
          collapsed={false}
          renderItem={({ item, collapseIcon, handler }) => this.getRenderItem(item, collapseIcon, handler)}
          onChange={this.onChange.bind(this)}
          ref={(ref) => {
            this.nestable = ref;
          }}
          handler={<DraggableButton />}
        />
        {reIndexed.map((item) => (
          <Fragment key={item.id}>
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
          </Fragment>
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
