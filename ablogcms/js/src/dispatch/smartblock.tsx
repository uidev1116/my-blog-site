import React from 'react';
import { render } from 'react-dom';
import {
  Paragraph,
  Heading1,
  Heading2,
  Heading3,
  Heading4,
  Heading5,
  Heading6,
  ListItem,
  Emphasis,
  OrderedList,
  BulletList,
  Blockquote,
  Code,
  Table,
  DefaultKeys,
  DefaultPlugins,
  Trash,
  MoveDown,
  MoveUp,
  Link,
  Strike,
  Strong,
  Underline,
  Embed,
  CustomBlock,
  CustomMark,
  Heading1Icon,
  Heading2Icon,
  Heading3Icon,
  Heading4Icon,
  Heading5Icon,
  Heading6Icon,
} from 'smartblock';
import Media from '../components/editor/media';
import Editor from '../components/editor/smartblock';

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

export default (editor: HTMLElement) => {
  // 2.11.1時点ではPaperEditorなのでその設定を吸収（PaperEditorがある場合はそちらを優先）
  const { Config } = ACMS;
  const config = {
    editMark: Config.PaperEditorEditMark ? Config.PaperEditorEditMark : Config.SmartBlockEditMark,
    bodyMark: Config.PaperEditorBodyMark ? Config.PaperEditorBodyMark : Config.SmartBlockBodyMark,
    titleMark: Config.PaperEditorTitleMark ? Config.PaperEditorTitleMark : Config.SmartBlockTitleMark,
    minHeight: Config.PaperEditorUnitMinHeight ? Config.PaperEditorUnitMinHeight : Config.SmartBlockUnitMinHeight,
    maxHeight: Config.PaperEditorUnitMaxHeight ? Config.PaperEditorUnitMaxHeight : Config.SmartBlockUnitMaxHeight,
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

  const editorEdit = editor.querySelector<HTMLElement>(config.editMark);
  const editorBody = editor.querySelector<HTMLInputElement>(config.bodyMark);
  const editorTitle = editor.querySelector<HTMLInputElement>(config.titleMark);

  const html = editorBody ? editorBody.value : '';
  const title = editorTitle ? editorTitle.value : '';
  const { useTitle } = editor.dataset;

  render(<Editor
    useTitle={useTitle === 'true'}
    html={html}
    title={title}
    minHeight={config.minHeight}
    maxHeight={config.maxHeight}
    extensions={ACMS.Config.SmartBlockConf(Extensions, editor, icons)}
    replacements={ACMS.Config.SmartBlockReplace(Extensions)}
    removes={ACMS.Config.SmartBlockRemoves}
    adds={ACMS.Config.SmartBlockAdds(Extensions)}
    onChange={(body) => {
      if (editorTitle) {
        editorTitle.value = body.title;
      }
      editorBody.value = JSON.stringify(body);
    }}
  />, editorEdit);
};
