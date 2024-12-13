'use client';

import React, { useState } from 'react';
import ReactQuill from 'react-quill';
import 'react-quill/dist/quill.bubble.css';
import { useSendMessage } from '@/hooks/useSendMessages';
import { Button } from '@/components/ui/button';
import { Loader2 } from 'lucide-react';
import { useParams } from 'next/navigation';

const toolbarOptions = [
  ['bold', 'italic', 'underline', 'strike'],
  ['blockquote', 'code-block'],
  [{ 'list': 'ordered'}, { 'list': 'bullet' }],
  ['clean'],
];

const editorStyle: React.CSSProperties = {
  flex: 1,
  maxHeight: '25vh',
  overflow: 'visible',
  backgroundColor: '#f9f9f9',
  borderRadius: '0.5rem',
  position: 'relative',
  zIndex: 1,
};

export default function MessageForm() {
  const params = useParams();
  const conversationId = params?.id ? Number(params.id) : 0;
  const [message, setMessage] = useState('');
  const [isExpanded, setIsExpanded] = useState(false);
  const { mutate: sendMessage, isPending } = useSendMessage(conversationId);
  
  const handleSendMessage = () => {
    if (!message.trim()) return;
    
    sendMessage(message);
    setMessage('');
    setIsExpanded(false);
  };

  return (
    <div className="message-form p-2 border-t flex items-center bg-[#f9f9f9]">
      <ReactQuill
        value={message}
        onChange={setMessage}
        theme="bubble"
        placeholder="Type your message..."
        modules={{ toolbar: toolbarOptions }}
        style={{ ...editorStyle, height: isExpanded ? '25vh' : 'auto' }}
        onFocus={() => setIsExpanded(true)}
        onBlur={() => setIsExpanded(false)}
      />
      <Button 
        onClick={handleSendMessage} 
        className="ml-2"
        disabled={isPending || !message.trim()}
      >
        {isPending ? (
          <Loader2 className="h-4 w-4 animate-spin" />
        ) : (
          'Send'
        )}
      </Button>
    </div>
  );
}