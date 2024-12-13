import React, { useState } from 'react';
import ReactQuill from 'react-quill';
import 'react-quill/dist/quill.bubble.css';

const toolbarOptions = [
  ['bold', 'italic', 'underline', 'strike'],
  ['blockquote', 'code-block'],
  [{ 'header': 1 }, { 'header': 2 }],
  [{ 'list': 'ordered'}, { 'list': 'bullet' }],
  [{ 'script': 'sub'}, { 'script': 'super' }],
  [{ 'indent': '-1'}, { 'indent': '+1' }],
  [{ 'direction': 'rtl' }],
  [{ 'size': ['small', false, 'large', 'huge'] }],
  [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
  [{ 'color': [] }, { 'background': [] }],
  [{ 'font': [] }],
  [{ 'align': [] }],
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
  const [message, setMessage] = useState('');
  const [isExpanded, setIsExpanded] = useState(false);

  const handleSendMessage = () => {
    console.log('Message sent:', message);
    setMessage('');
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
      <button onClick={handleSendMessage} className="send-button ml-2 bg-blue-500 text-white py-1 px-4 rounded">
        Send
      </button>
    </div>
  );
}