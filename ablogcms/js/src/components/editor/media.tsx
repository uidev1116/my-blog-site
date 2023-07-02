import * as React from 'react';
import { blockActive, Extension } from 'smartblock';
import { setBlockType } from 'prosemirror-commands';
import { render, unmountComponentAtNode } from 'react-dom';
import uuid from 'uuid';
import { EditorState } from 'prosemirror-state';

import CustomLayout from './custom-layout';
import ImageIcon from './image-icon';
import MediaInsert from '../media-insert';
import { MediaPlugin } from './plugins';
import { hasClass } from '../../lib/dom';

export default class Media extends Extension {
  imgClassName: string;

  imgFullClassName: string;

  captionClassName: string;

  constructor(props) {
    super(props);
    this.imgClassName = props.imgClassName;
    this.imgFullClassName = props.imgFullClassName;
    this.captionClassName = props.captionClassName;
  }

  get name() {
    return 'media';
  }

  get showMenu() {
    return true;
  }

  get group() {
    return 'block';
  }

  get hideMenuOnFocus() {
    return true;
  }

  get schema() {
    const { imgClassName } = this;
    return {
      content: 'inline*',
      isolating: true,
      group: 'block',
      selectable: true,
      attrs: {
        src: { default: '' },
        media_id: { default: '' },
        size: { default: '' },
        id: { default: '' },
        caption: { default: '' },
      },
      parseDOM: [
        {
          tag: 'figure',
          getAttrs(dom) {
            const img = dom.querySelector('img');
            if (!img) {
              return {};
            }
            return {
              src: img.getAttribute('src'),
              id: img.getAttribute('id'),
              media_id: img.getAttribute('data-media_id'),
              size: hasClass(img, imgClassName) ? 'small' : 'full',
            };
          },
        },
        {
          tag: 'img',
          getAttrs(dom) {
            return {
              src: dom.getAttribute('src'),
              id: dom.getAttribute('id'),
              media_id: dom.getAttribute('data-media_id'),
              size: hasClass(dom, imgClassName) ? 'small' : 'full',
            };
          },
        },
      ],
      toDOM: (node) => {
        if (node.attrs.media_id) {
          return [
            'figure',
            {
              class: this.className,
            },
            [
              'img',
              {
                src: node.attrs.src,
                class: node.attrs.size === 'full' ? this.imgFullClassName : this.imgClassName,
                id: node.attrs.id || uuid(),
                'data-media_id': node.attrs.media_id,
              },
            ],
            ['figcaption', { class: this.captionClassName }, 0],
          ];
        }
        return [
          'figure',
          {
            class: 'acms-admin-editor-media-placeholder',
          },
          [
            'span',
            {
              contenteditable: false,
              class: 'acms-admin-icon acms-admin-icon-unit-image',
            },
          ],
          ['figcaption', { class: 'caption', contenteditable: false }, 0],
        ];
      },
    };
  }

  get icon() {
    return <ImageIcon style={{ width: '24px', height: '24px' }} />;
  }

  get plugins() {
    return [MediaPlugin('キャプションを入力してください')];
  }

  active(state) {
    return blockActive(state.schema.nodes.media)(state);
  }

  enable(state) {
    return setBlockType(state.schema.nodes.media)(state);
  }

  onClick(state: EditorState, dispatch) {
    const div = document.createElement('div');
    document.body.appendChild(div);
    render(
      <MediaInsert
        tab="upload"
        filetype="image"
        onInsert={(items) => {
          const nodes = items.map((item) => {
            // eslint-disable-next-line camelcase
            const { media_edited: src, media_id } = item;
            return state.schema.nodes.media.createAndFill({
              src,
              media_id, // eslint-disable-line camelcase
              id: uuid(),
              size: 'small',
            });
          });
          const { pos } = state.selection.$anchor;
          dispatch(state.tr.insert(pos, nodes));
          unmountComponentAtNode(div);
        }}
        onClose={() => {
          unmountComponentAtNode(div);
        }}
      />,
      div,
    );
  }

  customLayout({ state, dispatch }, dom) {
    return <CustomLayout state={state} dispatch={dispatch} dom={dom} />;
  }
}
