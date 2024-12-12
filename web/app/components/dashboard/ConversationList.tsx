'use client';

import { ScrollArea } from "@/components/ui/scroll-area"
import { Card } from "@/components/ui/card"
import ConversationCard from "./ConversationCard"
import { useConversations } from "@/hooks/useConversations";
import { formatDistanceToNow } from 'date-fns';
import { Conversation } from "@/services/api/ConversationApi";

interface ConversationListProps {
  onSelectConversation?: (conversation: Conversation) => void;
  selectedId?: number;
}

export default function ConversationList({ onSelectConversation, selectedId }: ConversationListProps) {
  const { data: conversations, isLoading, error } = useConversations();

  console.log(conversations);
  

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
            lastMessage={conversation.messages[0]?.content || 'No messages yet'}
            timestamp={formatDistanceToNow(new Date(conversation.created_at), { addSuffix: true })}
            status={conversation.status}
            showSeparator={index < conversations.length - 1}
            isSelected={selectedId === conversation.id}
            onClick={() => onSelectConversation?.(conversation)}
          />
        ))}
      </ScrollArea>
    </Card>
  );
} 