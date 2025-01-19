import { useState, useEffect, useCallback } from 'react';
import useFirstMountState from './use-first-mount-state';

interface UseDisclosureProps {
  isOpen?: boolean;
  defaultIsOpen?: boolean;
  closeTimeout?: number;
  onAfterOpen?: () => void;
  onAfterClose?: () => void;
}

interface UseDisclosureReturn {
  isOpen: boolean;
  beforeClose: boolean;
  afterOpen: boolean;
  close: () => void;
  open: () => void;
}

/**
 * 開閉状態を管理するためのフックです。
 * このフックは、モーダルやアコーディオンなどの開閉状態を管理するために使用します。
 * 開閉状態の変更時にアニメーションを実行するためのプロパティやメソッドを提供します。
 *
 * @example
 * ```tsx
 * const MyComponent = () => {
 *   const { isOpen, beforeClose, afterOpen, close, open } = useDisclosure({
 *     closeTimeout: 300,
 *     onAfterOpen: () => console.log('Opened!'),
 *     onAfterClose: () => console.log('Closed!'),
 *   });
 *
 *   return (
 *     <div>
 *       <button onClick={open}>Open Modal</button>
 *       <button onClick={close}>Close Modal</button>
 *       {isOpen && (
 *         <div className={`modal ${afterOpen ? 'open' : ''} ${beforeClose ? 'closing' : ''}`}>
 *           Modal Content
 *         </div>
 *       )}
 *     </div>
 *   );
 * };
 *
 * ```tsx
 * const Accordion = () => {
 *   const { isOpen, beforeClose, afterOpen, open, close } = useDisclosure({
 *     closeTimeout: 300,
 *     onAfterOpen: () => console.log('Opened!'),
 *     onAfterClose: () => console.log('Closed!'),
 *   });
 *
 *   const toggle = () => {
 *     if (isOpen) {
 *       close();
 *     } else {
 *       open();
 *     }
 *   };
 *
 *   return (
 *     <div className="accordion-section">
 *       <button onClick={toggle} className="accordion-header">
 *         Open/Close
 *       </button>
 *       <div className={`accordion-content ${afterOpen ? 'open' : ''} ${beforeClose ? 'closing' : ''}`}>
 *         {isOpen && (
 *           <div className="accordion-body">
 *             Accordion Content
 *           </div>
 *         )}
 *       </div>
 *     </div>
 *   );
 * };
 *
 * @param {UseDisclosureProps} props - フックのプロパティ
 * @returns {UseDisclosureReturn} - 管理された状態と操作メソッドを返します
 */
const useDisclosure = ({
  isOpen: controlledIsOpen,
  defaultIsOpen = false,
  closeTimeout = 0,
  onAfterOpen,
  onAfterClose,
}: UseDisclosureProps = {}): UseDisclosureReturn => {
  const isFirstMount = useFirstMountState();
  const [internalState, setInternalState] = useState({
    isOpen: controlledIsOpen ?? defaultIsOpen,
    beforeClose: false,
    afterOpen: false,
  });

  useEffect(() => {
    if (controlledIsOpen !== undefined) {
      if (controlledIsOpen) {
        setInternalState((prevState) => ({
          ...prevState,
          isOpen: true,
          beforeClose: false,
        }));
      } else if (!isFirstMount && internalState.isOpen) {
        setInternalState((prevState) => ({
          ...prevState,
          beforeClose: true,
        }));
      }
    }
  }, [controlledIsOpen, isFirstMount, internalState.isOpen]);

  useEffect(() => {
    let animationFrame: number;
    if (internalState.isOpen && !internalState.afterOpen) {
      animationFrame = requestAnimationFrame(() => {
        setInternalState((prevState) => ({
          ...prevState,
          afterOpen: true,
        }));
        if (onAfterOpen) {
          onAfterOpen();
        }
      });
    }
    return () => {
      if (animationFrame) {
        cancelAnimationFrame(animationFrame);
      }
    };
    // 無限再レンダリング対策でonAfterOpenはdepsから削除
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [internalState.isOpen, internalState.afterOpen]);

  useEffect(() => {
    let timeoutId: number | undefined;
    if (internalState.beforeClose) {
      timeoutId = window.setTimeout(() => {
        setInternalState({
          isOpen: false,
          beforeClose: false,
          afterOpen: false,
        });
        if (onAfterClose) {
          onAfterClose();
        }
      }, closeTimeout);
    }
    return () => {
      if (timeoutId) {
        clearTimeout(timeoutId);
      }
    };
    // 無限再レンダリング対策でonAfterCloseはdepsから削除
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [internalState.beforeClose, closeTimeout]);

  const open = useCallback(() => {
    setInternalState({
      isOpen: true,
      beforeClose: false,
      afterOpen: false,
    });
  }, []);

  const close = useCallback(() => {
    setInternalState((prevState) => ({
      ...prevState,
      beforeClose: true,
    }));
  }, []);

  return {
    // beforeCloseがtrueの間はisOpenをtrueとすることで、閉じるアニメーションを実行する
    isOpen: internalState.isOpen || internalState.beforeClose,
    beforeClose: internalState.beforeClose,
    afterOpen: internalState.afterOpen,
    open,
    close,
  };
};

export default useDisclosure;
