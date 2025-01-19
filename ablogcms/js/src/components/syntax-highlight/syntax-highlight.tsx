import classnames from 'classnames';
import useSyntaxHighlight, { UseSyntaxHighlightOptions } from '../../hooks/use-syntax-highlight';

interface SyntaxHighlightProps
  extends React.HTMLAttributes<HTMLPreElement>,
    Pick<UseSyntaxHighlightOptions, 'language'> {
  options?: Partial<UseSyntaxHighlightOptions>;
  children: string;
}

const SyntaxHighlight = ({ children, language: languageProp, options, ...props }: SyntaxHighlightProps) => {
  const { value = '', language } = useSyntaxHighlight(children, { language: languageProp, ...options });

  return (
    <pre {...props}>
      {/* eslint-disable-next-line react/no-danger */}
      <code className={classnames(language, 'hljs')} dangerouslySetInnerHTML={{ __html: value }} />
    </pre>
  );
};

export default SyntaxHighlight;
