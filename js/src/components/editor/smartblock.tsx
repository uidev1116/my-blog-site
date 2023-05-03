import * as React from 'react';
import { SmartBlock, GlobalStyle, Extension } from 'smartblock';
import { Schema } from 'prosemirror-model';
import { MediaItem } from '../../types/media';

type ReturnValue = {
  title: string;
  html: string;
};

interface EditorProps {
  html: string;
  title: string;
  thumbnail: string;
  media_id: string; // eslint-disable-line camelcase
  useTitle: boolean;
  onChange(json: ReturnValue): void;
  extensions: Array<Extension>;
  replacements: Array<Extension>;
  removes: Array<string>;
  adds: Array<Extension>;
  maxHeight: number;
  minHeight: number;
}

interface EditorState {
  html: string;
  title: string;
  item: MediaItem;
  height?: number;
}

export default class Editor extends React.Component<EditorProps, EditorState> {
  schema!: Schema; // eslint-disable-line react/no-unused-class-component-methods

  container: React.MutableRefObject<HTMLDivElement>;

  constructor(props) {
    super(props);
    this.state = {
      html: '',
      title: '',
      item: {
        media_id: props.media_id,
        media_thumbnail: props.thumbnail,
      } as MediaItem,
    };
  }

  setEditorHeight() {
    const { maxHeight, minHeight } = this.props;
    if (this.container && this.container.current) {
      let height = this.container.current.offsetHeight;
      if (height > maxHeight) {
        height = maxHeight;
      }
      if (height < minHeight) {
        height = minHeight;
      }
      this.setState({
        height,
      });
    }
  }

  onChange({ html }) {
    const { title } = this.state;
    this.setState({
      html,
    });
    this.props.onChange({
      html,
      title,
    });
    this.setEditorHeight();
  }

  onTitleChange(title) {
    const { html } = this.state;
    this.setState({
      title,
    });
    this.props.onChange({
      html,
      title,
    });
    this.setEditorHeight();
  }

  render() {
    const { height } = this.state;
    const {
      html, title, useTitle, extensions, replacements, removes, adds,
    } = this.props;

    const replacedExtensions = extensions.map((extension) => {
      const replacement = replacements.find((item) => {
        if (extension.constructor.name === item.constructor.name) {
          return true;
        }
        return false;
      });
      if (replacement) {
        return replacement;
      }
      return extension;
    });

    const removedExtensions = replacedExtensions.filter((extension) => {
      const some = removes.some((remove) => {
        if (remove === extension.constructor.name) {
          return true;
        }
        return false;
      });
      return !some;
    });

    return (
      <div style={{ maxHeight: `${height}px` }}>
        <GlobalStyle />
        <SmartBlock
          getEditorRef={(container) => {
            this.container = container;
            this.setEditorHeight();
          }}
          full
          showTitle={useTitle}
          titleText={title}
          titlePlaceholder="タイトルを入力してください"
          extensions={[...removedExtensions, ...adds]}
          html={html}
          onInit={({ schema }) => {
            // eslint-disable-next-line react/no-unused-class-component-methods
            this.schema = schema;
          }}
          onChange={this.onChange.bind(this)}
          onTitleChange={this.onTitleChange.bind(this)}
        />
      </div>
    );
  }
}
