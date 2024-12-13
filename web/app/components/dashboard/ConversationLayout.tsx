'use client';

import { useState, useEffect } from 'react';
import { useRouter } from 'next/navigation';
import ConversationList from '@/app/components/dashboard/ConversationList';
import ConversationView from '@/app/components/dashboard/ConversationView';
import { Conversation, ConversationApi } from '@/services/api/ConversationApi';
import { useOpenConversations } from '@/hooks/useConversationSelection';

interface ConversationLayoutProps {
  initialConversationId?: number;
}

export default function ConversationLayout({ initialConversationId }: ConversationLayoutProps) {
  const [openConversation, setOpenConversation] = useState<Conversation | null>(null);
  const setOpen = useOpenConversations((state) => state.setOpen);
  const router = useRouter();

  useEffect(() => {
    if (initialConversationId) {
      setOpen(true);
      const fetchConversation = async () => {
        try {
          const response = await new ConversationApi().getConversation(initialConversationId);
          setOpenConversation(response.data);
        } catch (error) {
          console.error('Failed to fetch conversation:', error);
        }
      };

      fetchConversation();
    }
  }, [initialConversationId]);

  const handleConversationSelect = (conversation: Conversation) => {
    setOpenConversation(conversation);
    setOpen(true);
    router.push(`/a/conversations/${conversation.id}`);
  };

  return (
    <div className="h-[calc(100vh-theme(spacing.16))] flex">
      {/* Left panel - Conversation List */}
      <div className="w-80 border-r">
        <ConversationList 
          onOpenConversation={handleConversationSelect}
          openId={openConversation?.id}
        />
      </div>

      {/* Right panel - Conversation Messages */}
      <div className="flex-1 flex flex-col">
        {openConversation ? (
          <ConversationView conversation={openConversation} />
        ) : (
          <div className="flex-1 flex items-center justify-center text-muted-foreground">
            Select a conversation to view messages
          </div>
        )}
      </div>
    </div>
  );
} 