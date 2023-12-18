import { Decoration, DecorationSet } from 'prosemirror-view';
import { Plugin } from 'smartblock';
import { findChildren } from 'prosemirror-utils';

export const MediaPlugin = () => new Plugin({
  props: {
    decorations(state) {
      const { doc } = state;
      const medias = findChildren(
        doc,
        (node) => {
          if (node.type.name === 'media') {
            return true;
          }
          return false;
        },
        true,
      );
      const decorations = [];
      medias.forEach((media) => {
        if (!media.node.content || !media.node.content.size) {
          if (media.node.attrs.media_id) {
            decorations.push(
              Decoration.node(media.pos, media.pos + media.node.nodeSize, {
                class: 'empty-node',
              }),
            );
          }
        }
      });
      if (decorations.length) {
        return DecorationSet.create(doc, decorations);
      }
    },
  },
  filterTransaction: () => true,
});
