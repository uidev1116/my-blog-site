import { MutableRefObject, useCallback, useEffect, useRef, useState } from 'react';
import SmartBlock from 'smartblock/components/smartblock';
import type { Extension } from 'smartblock';
import { Schema } from 'smartblock/pm/model';

import 'smartblock/css/smartblock.css';

type ReturnValue = {
  title: string;
  html: string;
};

interface EditorProps {
  html: string;
  title: string;
  useTitle: boolean;
  onChange(value: ReturnValue): void;
  extensions: Array<Extension>;
  replacements: Array<Extension>;
  removes: Array<string>;
  adds: Array<Extension>;
  maxHeight: number;
  minHeight: number;
  titlePlaceholder: string;
}

const RichEditor = ({
  maxHeight,
  minHeight,
  onChange,
  html,
  title,
  useTitle,
  extensions,
  replacements,
  removes,
  adds,
  titlePlaceholder,
}: EditorProps) => {
  const valueRef = useRef({
    html: '',
    title: '',
  });

  const [height, setHeight] = useState(0);
  const container = useRef<HTMLDivElement | null>(null);
  const schemaRef = useRef<Schema | null>(null);

  const setEditorHeight = useCallback(() => {
    if (container.current) {
      let height = container.current.offsetHeight;
      if (height > maxHeight) {
        height = maxHeight;
      }
      if (height < minHeight) {
        height = minHeight;
      }
      setHeight(height);
    }
  }, [maxHeight, minHeight]);

  useEffect(() => {
    setEditorHeight();
  }, [setEditorHeight]);

  const handleChange = useCallback(
    ({ html }: { html: string }) => {
      valueRef.current = { ...valueRef.current, html };
      onChange(valueRef.current);

      // update a component while rendering a different component 対策で、setTimeoutを使用
      setTimeout(() => {
        setEditorHeight();
      }, 0);
    },
    [onChange, setEditorHeight]
  );

  const handleTitleChange = useCallback(
    (title: string) => {
      valueRef.current = { ...valueRef.current, title };
      onChange(valueRef.current);
      // update a component while rendering a different component 対策で、setTimeoutを使用
      setTimeout(() => {
        setEditorHeight();
      }, 0);
    },
    [onChange, setEditorHeight]
  );

  const replacedExtensions = extensions.map((extension) => {
    const replacement = replacements.find((item) => extension.constructor.name === item.constructor.name);
    return replacement || extension;
  });

  const removedExtensions = replacedExtensions.filter(
    (extension) => !removes.some((remove) => remove === extension.constructor.name)
  );

  return (
    <div style={{ maxHeight: `${height}px` }}>
      <SmartBlock
        getEditorRef={(editorRef: MutableRefObject<HTMLDivElement>) => {
          container.current = editorRef.current;
          setEditorHeight();
        }}
        full
        showTitle={useTitle}
        titleText={title}
        titlePlaceholder={titlePlaceholder}
        extensions={[...removedExtensions, ...adds]}
        html={html}
        onInit={({ schema }) => {
          schemaRef.current = schema;
        }}
        onChange={handleChange}
        onTitleChange={handleTitleChange}
      />
    </div>
  );
};

export default RichEditor;
