import { cn } from "@/lib/utils"
import { Separator } from "@/components/ui/separator"
import { Badge } from "@/components/ui/badge"

interface ConversationCardProps {
  clientName: string
  lastMessage: string
  timestamp: string
  status: 'pending' | 'active' | 'closed'
  isSelected?: boolean
  onClick?: () => void
  showSeparator?: boolean
}

export default function ConversationCard({
  clientName,
  lastMessage,
  timestamp,
  status,
  isSelected = false,
  onClick,
  showSeparator = true,
}: ConversationCardProps) {
  const statusColors = {
    pending: "bg-yellow-500",
    active: "bg-green-500",
    closed: "bg-gray-500"
  };

  return (
    <>
      <div
        onClick={onClick}
        className={cn(
          "p-4 hover:bg-accent cursor-pointer transition-colors",
          isSelected && "bg-accent"
        )}
      >
        <div className="flex items-center justify-between mb-2">
          <span className="font-medium">{clientName}</span>
          <Badge variant="secondary" className={cn("text-xs", statusColors[status])}>
            {status}
          </Badge>
        </div>
        <p className="text-sm text-muted-foreground truncate mb-2">
          {lastMessage}
        </p>
        <span className="text-xs text-muted-foreground">
          {timestamp}
        </span>
      </div>
      {showSeparator && <Separator />}
    </>
  )
} 