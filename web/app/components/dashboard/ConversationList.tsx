'use client';

import { ScrollArea } from "@/components/ui/scroll-area"
import { Card } from "@/components/ui/card"
import ConversationCard from "./ConversationCard"
import { useConversations } from "@/hooks/useConversations";
import { Conversation } from "@/services/api/ConversationService";
import { getTimestamp } from "@/lib/utils"

interface ConversationListProps {
  onOpenConversation?: (conversation: Conversation) => void;
  openId?: number;
}

export default function ConversationList({ onOpenConversation, openId }: ConversationListProps) {
  const { data: conversations, isLoading, error } = useConversations();

  if (isLoading) return <div className="p-4">Loading conversations...</div>;
  if (error) return <div className="p-4 text-red-500">Error loading conversations</div>;

  return (
    <Card className="h-full border-none rounded-none">
      <div className="p-4 border-b">
        <h2 className="text-lg font-semibold">Conversations</h2>
      </div>
      <ScrollArea className="h-[calc(100vh-10rem)]">
        {conversations?.map((conversation, index) => (
          <ConversationCard
            key={conversation.id}
            clientName={conversation.client.name}
            lastMessage={conversation.messages.data[0]?.content || 'No messages yet'}
            timestamp={getTimestamp(conversation.created_at)}
            status={conversation.status}
            showSeparator={index < conversations.length - 1}
            isOpen={openId === conversation.id}
            onClick={() => onOpenConversation?.(conversation)}
          />
        ))}
      </ScrollArea>
    </Card>
  );
} 