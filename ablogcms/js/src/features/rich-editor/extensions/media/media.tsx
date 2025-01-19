import { useState } from 'react';
import { blockActive, Dispatch, Extension, ExtensionProps } from 'smartblock';
import { setBlockType } from 'smartblock/pm/commands';
import { v4 as uuid } from 'uuid';
import { EditorState } from 'smartblock/pm/state';

import { Node } from 'smartblock/pm/model';
import { createRoot } from 'react-dom/client';
import Layout from './components/layout';
import ImageIcon from './icons/image';
import MediaInsert from '../../../media/components/media-insert/media-insert';
import { MediaPlugin } from './plugins';

/* eslint @typescript-eslint/ban-ts-comment: 0 */

export default class Media extends Extension {
  imgClassName: string;

  imgFullClassName: string;

  captionClassName: string;

  constructor(props: ExtensionProps) {
    super(props);
    this.imgClassName = props?.imgClassName || '';
    this.imgFullClassName = props?.imgFullClassName || '';
    this.captionClassName = props?.captionClassName || '';
  }

  // @ts-ignore
  get name() {
    return 'media';
  }

  // @ts-ignore
  get showMenu() {
    return true;
  }

  // @ts-ignore
  get group() {
    return 'block';
  }

  // @ts-ignore
  get hideMenuOnFocus() {
    return true;
  }

  // @ts-ignore
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
          getAttrs(dom: HTMLElement) {
            const img = dom.querySelector('img');
            if (!img) {
              return {};
            }
            return {
              src: img.getAttribute('src'),
              id: img.getAttribute('id'),
              media_id: img.getAttribute('data-media_id'),
              size: img.classList.contains(imgClassName) ? 'small' : 'full',
            };
          },
        },
        {
          tag: 'img',
          getAttrs(dom: HTMLElement) {
            return {
              src: dom.getAttribute('src'),
              id: dom.getAttribute('id'),
              media_id: dom.getAttribute('data-media_id'),
              size: dom.classList.contains(imgClassName) ? 'small' : 'full',
            };
          },
        },
      ],
      toDOM: (node: Node) => {
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

  // @ts-ignore
  get icon() {
    return <ImageIcon style={{ width: '24px', height: '24px' }} />;
  }

  // @ts-ignore
  get plugins() {
    return [MediaPlugin()];
  }

  // @ts-ignore
  active(state: EditorState) {
    return blockActive(state.schema.nodes.media)(state);
  }

  // @ts-ignore
  enable(state: EditorState) {
    return setBlockType(state.schema.nodes.media)(state);
  }

  onClick(state: EditorState, dispatch: Dispatch) {
    const div = document.createElement('div');
    document.body.appendChild(div);
    const root = createRoot(div);
    const Renderer = () => {
      const [isOpen, setIsOpen] = useState(true);

      return (
        <MediaInsert
          isOpen={isOpen}
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
            }) as Node[];
            const { pos } = state.selection.$anchor;
            dispatch(state.tr.insert(pos, nodes));
            setIsOpen(false);
            setTimeout(() => {
              root.unmount();
              document.body.removeChild(div);
            }, 300);
          }}
          onClose={() => {
            setIsOpen(false);
            setTimeout(() => {
              root.unmount();
              document.body.removeChild(div);
            }, 300);
          }}
        />
      );
    };
    root.render(<Renderer />);
  }

  // @ts-ignore
  customLayout({ state, dispatch }: { state: EditorState; dispatch: Dispatch }, dom) {
    return <Layout state={state} dispatch={dispatch} dom={dom} />;
  }
}
