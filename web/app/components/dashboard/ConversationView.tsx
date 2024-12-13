'use client';

import { ScrollArea } from "@/components/ui/scroll-area";
import { Conversation } from "@/services/api/ConversationApi";
import { formatDistanceToNow } from "date-fns";

interface Message {
  id: number;
  content: string;
  sender_type: string;
  sender_id: number;
  created_at: string;
  read_at: string | null;
}

interface ConversationViewProps {
  conversation: Conversation;
}

export default function ConversationView({ conversation }: ConversationViewProps) {
  return (
    <div className="flex-1 flex flex-col">
      {/* Conversation Header */}
      <div className="p-4 border-b">
        <div className="flex justify-between items-center">
          <div>
            <h2 className="font-semibold">{conversation.client.name}</h2>
            <p className="text-sm text-muted-foreground">
              Started {formatDistanceToNow(new Date(conversation.created_at), { addSuffix: true })}
            </p>
          </div>
          <div className="text-sm text-muted-foreground">
            Status: {conversation.status}
          </div>
        </div>
      </div>

      {/* Messages */}
      <ScrollArea className="flex-1 p-4">
        <div className="space-y-4">
          {conversation.messages.data.map((message: Message) => (
            <div
              key={message.id}
              className={`flex ${
                message.sender_id === conversation.client.id ? 'justify-start' : 'justify-end'
              }`}
            >
              <div
                className={`max-w-[80%] rounded-lg p-3 ${
                  message.sender_id === conversation.client.id
                    ? 'bg-accent'
                    : 'bg-primary text-primary-foreground'
                }`}
              >
                <p className="text-sm">{message.content}</p>
                <span className="text-xs opacity-70 mt-1 block">
                  {formatDistanceToNow(new Date(message.created_at), { addSuffix: true })}
                </span>
              </div>
            </div>
          ))}
        </div>
      </ScrollArea>
    </div>
  );
} 