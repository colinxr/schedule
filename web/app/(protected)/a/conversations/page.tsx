'use client';

import { useState } from 'react';
import ConversationList from '@/app/components/dashboard/ConversationList';
import ConversationView from '@/app/components/dashboard/ConversationView';
import { Conversation } from '@/services/api/ConversationApi';
import { useConversationSelection } from '@/hooks/useConversationSelection';
import { ConversationSelectionStore } from '@/hooks/useConversationSelection';

export default function ConversationsPage() {
  const [selectedConversation, setSelectedConversation] = useState<Conversation | null>(null);
  const setSelected = useConversationSelection((state: ConversationSelectionStore) => state.setSelected);

  const handleConversationSelect = (conversation: Conversation) => {
    setSelectedConversation(conversation);
    setSelected(true);
  };

  return (
    <div className="h-[calc(100vh-theme(spacing.16))] flex">
      {/* Left panel - Conversation List */}
      <div className="w-80 border-r">
        <ConversationList 
          onSelectConversation={handleConversationSelect}
          selectedId={selectedConversation?.id}
        />
      </div>

      {/* Right panel - Conversation Messages */}
      <div className="flex-1 flex flex-col">
        {selectedConversation ? (
          <ConversationView conversation={selectedConversation} />
        ) : (
          <div className="flex-1 flex items-center justify-center text-muted-foreground">
            Select a conversation to view messages
          </div>
        )}
      </div>
    </div>
  );
} 