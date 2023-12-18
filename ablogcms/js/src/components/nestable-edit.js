import { Component } from 'react';
import clone from 'clone';
import PropTypes from 'prop-types';
import { nestify, flatify } from 'nestify';

import StateManager from '../lib/state-manager';

/* eslint react/no-unused-class-component-methods: 0 */

export default class NestableEdit extends Component {
  static propTypes = {
    message: PropTypes.object, // eslint-disable-line react/forbid-prop-types
  };

  static defaultProps = {
    message: {},
  };

  onInitialize({ items }) {
    this.setup(items);
    this.manager = new StateManager();
    this.manager.pushState(this.state);
  }

  setup(items) {
    const def = this.defineDefaultItem();
    if (items.length === 0) {
      items.push({ parent: null, ...def });
    }
    items.forEach((item, i) => {
      item.id = i + 1;
    });

    this.state = {
      items,
      preventAnimation: false,
      removeAlert: false,
      changed: false,
    };
  }

  findItemById(id) {
    return this.state.items.find((target) => target.id === id);
  }

  findChildById(id) {
    return this.state.items.find((target) => target.parent === id);
  }

  addChild(item) {
    const def = this.defineDefaultItem();
    const items = clone(this.state.items);
    const { parent } = item;
    const index = items.findIndex((target) => target.id === item.id);
    const newitem = {
      parent,
      id: this.state.items.length + 1,
      ...def,
    };
    const inserted = [...items.slice(0, index + 1), newitem, ...items.slice(index + 1)];
    this.setState({ items: inserted });
    this.manager.pushState({ items: inserted });
  }

  addItem(item = {}) {
    const def = this.defineDefaultItem();
    const items = clone(this.state.items);
    const newitem = { id: this.state.items.length + 1, ...def, ...item };
    const inserted = [...items, newitem];
    this.setState({ items: inserted });
    this.manager.pushState({ items: inserted });
  }

  redo() {
    const state = this.manager.redo();
    if (state) {
      this.setState({
        items: state.items,
      });
    }
  }

  undo() {
    const state = this.manager.undo();
    if (state) {
      this.setState({
        items: state.items,
      });
    }
  }

  updateItem(id, key, value, changed = true) {
    // eslint-disable-next-line react/no-access-state-in-setstate
    const { items } = this.state;
    items.forEach((item) => {
      if (item.id === id) {
        item[key] = value;
      }
    });
    this.setState({ items, changed });
    this.manager.pushState(this.state);
  }

  removeAlert() {
    this.setState({ removeAlert: true });
  }

  selectItem(id) {
    // eslint-disable-next-line react/no-access-state-in-setstate
    const { items } = this.state;
    items.forEach((item) => {
      if (item.id === id) {
        item.selected = true;
      } else {
        item.selected = false;
      }
    });
    this.setState({ items, changed: true });
    this.manager.pushState(this.state);
  }

  removeItem(id) {
    if (confirm(this.props.message.onRemove)) {
      // eslint-disable-line no-alert, no-console
      const { items } = this.state;
      const removeIndex = items.findIndex((item) => item.id === id);
      items.forEach((item) => {
        if (item.parent === id) {
          item.parent = items[removeIndex].parent;
        }
      });
      const removed = [...items.slice(0, removeIndex), ...items.slice(removeIndex + 1)];
      this.setState({
        items: this.reIndex(removed),
        changed: true,
      });

      this.manager.pushState({
        items: removed,
        changed: true,
      });
    }
  }

  toggleItem(item) {
    this.setState({
      preventAnimation: false,
    });
    this.nestable.onToggleCollapse(item);
  }

  getNested(items) {
    return nestify(
      {
        id: 'id',
        parentId: 'parent',
        children: 'children',
      },
      clone(items),
    );
  }

  defineDefaultItem() {
    return {};
  }

  onChange(items) {
    items.forEach((item) => {
      item.parent = null;
    });
    const flat = flatify(
      {
        id: 'id',
        parentId: 'parent',
        children: 'children',
      },
      clone(items),
    );
    this.setState({
      items: flat,
      preventAnimation: true,
      changed: true,
    });
    this.manager.pushState(this.state);
  }

  reIndex(items) {
    items.forEach((item) => {
      const index = items.findIndex((obj) => obj.id === item.parent);
      if (index >= 0) {
        item.parent = index + 1;
      } else {
        item.parent = null;
      }
    });
    items.forEach((item, i) => {
      item.id = i + 1;
    });
    return items;
  }
}
