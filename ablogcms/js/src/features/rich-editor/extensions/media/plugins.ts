import { Decoration, DecorationSet } from 'smartblock/pm/view';
import { Plugin } from 'smartblock/pm/state';
import { findChildren } from 'smartblock/pm/utils';

export const MediaPlugin = () =>
  new Plugin({
    props: {
      // eslint-disable-next-line @typescript-eslint/ban-ts-comment
      // @ts-ignore
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
          true
        );
        const decorations: Decoration[] = [];
        medias.forEach((media) => {
          if (!media.node.content || !media.node.content.size) {
            if (media.node.attrs.media_id) {
              decorations.push(
                Decoration.node(media.pos, media.pos + media.node.nodeSize, {
                  class: 'empty-node',
                })
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
