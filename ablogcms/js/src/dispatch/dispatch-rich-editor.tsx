import { Suspense, lazy } from 'react';
import Expand from 'ui-expand';
import { render } from '../utils/react';

export default async function dispatchRichEditor(context: Document | Element) {
  const elements = context.querySelectorAll<HTMLElement>(ACMS.Config.SmartBlockMark);

  if (elements.length === 0) {
    return;
  }

  // 2.11.1時点ではPaperEditorなのでその設定を吸収（PaperEditorがある場合はそちらを優先）
  const { Config } = ACMS;
  const config = {
    editMark: Config.PaperEditorEditMark ? Config.PaperEditorEditMark : Config.SmartBlockEditMark,
    bodyMark: Config.PaperEditorBodyMark ? Config.PaperEditorBodyMark : Config.SmartBlockBodyMark,
    titleMark: Config.PaperEditorTitleMark ? Config.PaperEditorTitleMark : Config.SmartBlockTitleMark,
    minHeight: Config.PaperEditorUnitMinHeight ? Config.PaperEditorUnitMinHeight : Config.SmartBlockUnitMinHeight,
    maxHeight: Config.PaperEditorUnitMaxHeight ? Config.PaperEditorUnitMaxHeight : Config.SmartBlockUnitMaxHeight,
    titlePlaceholder: Config.SmartBlockTitlePlaceholder,
  };
  if (ACMS.Config.PaperEditorConf) {
    ACMS.Config.SmartBlockConf = ACMS.Config.PaperEditorConf;
  }
  if (ACMS.Config.PaperEditorReplace) {
    ACMS.Config.SmartBlockReplace = ACMS.Config.PaperEditorReplace;
  }
  if (ACMS.Config.PaperEditorRemoves) {
    ACMS.Config.SmartBlockRemoves = ACMS.Config.PaperEditorRemoves;
  }
  if (ACMS.Config.PaperEditorAdds) {
    ACMS.Config.SmartBlockAdds = ACMS.Config.PaperEditorAdds;
  }

  if (elements.length === 0) {
    return;
  }

  const {
    Paragraph,
    Heading1,
    Heading2,
    Heading3,
    Heading4,
    Heading5,
    Heading6,
    ListItem,
    BulletList,
    OrderedList,
    Blockquote,
    Code,
    Table,
    Media,
    Emphasis,
    Underline,
    Strike,
    Link,
    MoveDown,
    MoveUp,
    Trash,
    Strong,
    Embed,
    DefaultKeys,
    DefaultPlugins,
    CustomBlock,
    CustomMark,
    Heading1Icon,
    Heading2Icon,
    Heading3Icon,
    Heading4Icon,
    Heading5Icon,
    Heading6Icon,
  } = await import(/* webpackChunkName: "rich-editor-extensions" */ '../features/rich-editor/extensions');
  const RichEditor = lazy(
    () => import(/* webpackChunkName: "rich-editor" */ '../features/rich-editor/components/rich-editor/rich-editor')
  );

  const Extensions = {
    Paragraph,
    Heading1,
    Heading2,
    Heading3,
    Heading4,
    Heading5,
    Heading6,
    ListItem,
    BulletList,
    OrderedList,
    Blockquote,
    Code,
    Table,
    Media,
    Emphasis,
    Underline,
    Strike,
    Link,
    MoveDown,
    MoveUp,
    Trash,
    Strong,
    Embed,
    DefaultKeys,
    DefaultPlugins,
    CustomBlock,
    CustomMark,
  };

  const icons = {
    Heading1Icon: <Heading1Icon style={{ width: '24px', height: '24px' }} />,
    Heading2Icon: <Heading2Icon style={{ width: '24px', height: '24px' }} />,
    Heading3Icon: <Heading3Icon style={{ width: '24px', height: '24px' }} />,
    Heading4Icon: <Heading4Icon style={{ width: '24px', height: '24px' }} />,
    Heading5Icon: <Heading5Icon style={{ width: '24px', height: '24px' }} />,
    Heading6Icon: <Heading6Icon style={{ width: '24px', height: '24px' }} />,
  };

  elements.forEach((element) => {
    const editorEdit = element.querySelector<HTMLElement>(config.editMark);
    if (editorEdit === null) {
      return;
    }
    const editorBody = element.querySelector<HTMLInputElement>(config.bodyMark);
    const editorTitle = element.querySelector<HTMLInputElement>(config.titleMark);

    const html = editorBody ? editorBody.value : '';
    const title = editorTitle ? editorTitle.value : '';
    const { useTitle } = element.dataset;

    render(
      <Suspense fallback={null}>
        <RichEditor
          useTitle={useTitle === 'true'}
          html={html}
          title={title}
          titlePlaceholder={config.titlePlaceholder}
          minHeight={config.minHeight}
          maxHeight={config.maxHeight}
          extensions={ACMS.Config.SmartBlockConf(Extensions, element, icons)}
          replacements={ACMS.Config.SmartBlockReplace(Extensions)}
          removes={ACMS.Config.SmartBlockRemoves}
          adds={ACMS.Config.SmartBlockAdds(Extensions)}
          onChange={(body) => {
            if (editorBody) {
              editorBody.value = JSON.stringify(body);
            }
          }}
        />
      </Suspense>,
      editorEdit
    );

    /**
     * Expand SmartBlock
     * 1つの要素に対して重複して実行された場合、拡大と縮小が同時に動作してしまう問題対策で
     * ui-expand-initializedクラスを付与 & カスタムフィールドグループのテンプレート要素内の要素は除外
     */
    const { fieldgroupSortableItemTemplateMark } = ACMS.Config;
    const expands = context.querySelectorAll<HTMLElement>(
      `.js-expand:not(.ui-expand-initialized):not(${fieldgroupSortableItemTemplateMark} .js-expand)`
    );
    // eslint-disable-next-line no-new
    new Expand(expands, {
      beforeOpen: (element) => {
        element?.classList.add('js-acms-expanding');
        element?.closest<HTMLElement>('.js-visible-on-ui-expanding')?.style.setProperty('overflow', 'visible');
      },
      onOpen: (element) => {
        element?.querySelectorAll<HTMLElement>('.js-expand-icon').forEach((icon) => {
          icon.classList.remove('acms-admin-icon-expand-arrow');
          icon.classList.add('acms-admin-icon-contract-arrow');
        });
        element?.classList.add('js-acms-expanded');
      },
      beforeClose: (element) => {
        element?.closest<HTMLElement>('.js-visible-on-ui-expanding')?.style.removeProperty('overflow');
      },
      onClose: (element) => {
        element?.classList.remove('js-acms-expanding');
        element?.classList.remove('js-acms-expanded');
        element?.querySelectorAll<HTMLElement>('.js-expand-icon').forEach((icon) => {
          icon.classList.add('acms-admin-icon-expand-arrow');
          icon.classList.remove('acms-admin-icon-contract-arrow');
        });
      },
    });
    expands.forEach((expand) => {
      expand.classList.add('ui-expand-initialized');
    });
  });
}
