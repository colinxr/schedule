'use client';

import { useState } from 'react';
import ConversationList from '@/app/components/dashboard/ConversationList';
import { Separator } from '@/components/ui/separator';
import { Conversation } from '@/services/api/ConversationApi';
import { ScrollArea } from '@/components/ui/scroll-area';
import { formatDistanceToNow } from 'date-fns';

export default function ConversationsPage() {
  const [selectedConversation, setSelectedConversation] = useState<Conversation | null>(null);

  return (
    <div className="h-[calc(100vh-theme(spacing.16))] flex">
      {/* Left panel - Conversation List */}
      <div className="w-80 border-r">
        <ConversationList 
          onSelectConversation={setSelectedConversation}
          selectedId={selectedConversation?.id}
        />
      </div>

      {/* Right panel - Conversation Messages */}
      <div className="flex-1 flex flex-col">
        {selectedConversation ? (
          <>
            {/* Conversation Header */}
            <div className="p-4 border-b">
              <div className="flex justify-between items-center">
                <div>
                  <h2 className="font-semibold">{selectedConversation.client.name}</h2>
                  <p className="text-sm text-muted-foreground">
                    Started {formatDistanceToNow(new Date(selectedConversation.created_at), { addSuffix: true })}
                  </p>
                </div>
                <div className="text-sm text-muted-foreground">
                  Status: {selectedConversation.status}
                </div>
              </div>
            </div>

            {/* Messages */}
            <ScrollArea className="flex-1 p-4">
              <div className="space-y-4">
                {selectedConversation.messages.data.map((message) => (
                  <div
                    key={message.id}
                    className={`flex ${
                      message.sender_id === selectedConversation.client.id ? 'justify-start' : 'justify-end'
                    }`}
                  >
                    <div
                      className={`max-w-[80%] rounded-lg p-3 ${
                        message.sender_id === selectedConversation.client.id
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
          </>
        ) : (
          <div className="flex-1 flex items-center justify-center text-muted-foreground">
            Select a conversation to view messages
          </div>
        )}
      </div>
    </div>
  );
} 