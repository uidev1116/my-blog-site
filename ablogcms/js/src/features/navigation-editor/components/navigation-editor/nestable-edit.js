import { Component } from 'react';
import clone from 'clone';
import PropTypes from 'prop-types';
import { nestify, flatify } from 'nestify';
import { v4 as uuidv4 } from 'uuid';

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
  }

  setup(items) {
    const def = this.defineDefaultItem();
    if (items.length === 0) {
      items.push({ parent: null, ...def });
    }
    items.forEach((item, i) => {
      item.id = i + 1;
      item.uuid = uuidv4();
    });

    this.state = {
      items,
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
  }

  updateItem(id, key, value, changed = true) {
    const { items } = this.state;
    const newItems = items.map((item) => (item.id === id ? { ...item, [key]: value } : item));
    this.setState({ items: newItems, changed });
  }

  removeAlert() {
    this.setState({ removeAlert: true });
  }

  removeItem(id) {
    if (confirm(this.props.message.onRemove)) {
      // eslint-disable-line no-alert, no-console
      const { items } = this.state;
      const removeItem = items.find((item) => item.id === id);
      const newItems = items
        .filter((item) => item.id !== id)
        .map((item) => {
          if (item.parent === id) {
            return { ...item, parent: removeItem.parent };
          }
          return item;
        });
      this.setState({
        items: this.reIndex(newItems),
        changed: true,
      });
    }
  }

  getNested(items) {
    return nestify(
      {
        id: 'id',
        parentId: 'parent',
        children: 'children',
      },
      clone(items)
    );
  }

  defineDefaultItem() {
    return {};
  }

  onChange({ items }) {
    const newItems = items.map((item) => ({ ...item, parent: null }));
    const flatifiedItems = flatify(
      {
        id: 'id',
        parentId: 'parent',
        children: 'children',
      },
      clone(newItems)
    );
    this.setState({
      items: flatifiedItems,
      changed: true,
    });
  }

  reIndex(items) {
    const newItems = items.map((item) => {
      const index = items.findIndex(({ id }) => id === item.parent);
      if (index >= 0) {
        return { ...item, parent: index + 1 };
      }

      return { ...item, parent: null };
    });

    return newItems.map((item, i) => ({
      ...item,
      id: i + 1,
    }));
  }
}
