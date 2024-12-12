import { ScrollArea } from "@/components/ui/scroll-area"
import { Card } from "@/components/ui/card"
import ConversationCard from "./ConversationCard"
import { useState } from "react"

interface Conversation {
  id: string
  clientName: string
  lastMessage: string
  timestamp: string
}

export default function ConversationList() {
  const [selectedId, setSelectedId] = useState<string | null>(null)

  // This is mock data - replace with actual data fetching
  const conversations: Conversation[] = [
    {
      id: '1',
      clientName: 'John Doe',
      lastMessage: 'Thanks for your help with the project',
      timestamp: '2h ago'
    },
    {
      id: '2',
      clientName: 'Sarah Smith',
      lastMessage: 'When can we schedule a meeting?',
      timestamp: '3h ago'
    },
    // Add more mock conversations for scrolling test
    ...Array.from({ length: 10 }, (_, i) => ({
      id: `${i + 3}`,
      clientName: `Client ${i + 3}`,
      lastMessage: 'This is a sample message...',
      timestamp: '5h ago'
    }))
  ]

  return (
    <Card className="h-full border-none rounded-none">
      <div className="p-4 border-b">
        <h2 className="text-lg font-semibold">Conversations</h2>
      </div>
      <ScrollArea className="h-[calc(100vh-10rem)]">
        {conversations.map((conversation, index) => (
          <ConversationCard
            key={conversation.id}
            clientName={conversation.clientName}
            lastMessage={conversation.lastMessage}
            timestamp={conversation.timestamp}
            isSelected={selectedId === conversation.id}
            onClick={() => setSelectedId(conversation.id)}
            showSeparator={index < conversations.length - 1}
          />
        ))}
      </ScrollArea>
    </Card>
  )
} 